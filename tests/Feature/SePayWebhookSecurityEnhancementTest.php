<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankPaymentSession;
use App\Models\BankTransaction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SePayWebhookSecurityEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Category $category;

    protected Product $product;

    protected ProductVariant $variant;

    protected Address $address;

    protected string $webhookToken = 'sepay_secret_token_xyz123';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('bank.webhook_token', $this->webhookToken);
        Config::set('bank.webhook_hmac_secret', null);
        Config::set('bank.account_number', '0369682734');

        $this->customer = User::factory()->create([
            'name' => 'Khách Test SePay',
            'email' => 'sepay_customer@haomilk.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->category = Category::create(['name' => 'Bỉm tã trẻ em']);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bỉm Molfix Jumbo',
            'base_price' => 200000,
            'status' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 200000,
            'stock_quantity' => 20,
        ]);

        $this->address = Address::create([
            'user_id' => $this->customer->id,
            'receiver_name' => 'Khách Test SePay',
            'receiver_phone' => '0988776655',
            'province' => 'Hà Nội',
            'detail_address' => 'Số 123 Giải Phóng',
            'is_default' => true,
        ]);
    }

    protected function createPaymentSession(
        string $code,
        float $amount = 200000,
        int $qty = 1,
        string $status = 'pending'
    ): BankPaymentSession {
        return BankPaymentSession::create([
            'session_code' => $code,
            'user_id' => $this->customer->id,
            'bank_id' => 'MB',
            'bank_name' => 'MB Bank',
            'account_number' => '0369682734',
            'account_name' => 'CUA HANG HUONG MIT',
            'amount' => $amount,
            'transfer_content' => $code,
            'qr_url' => 'https://img.vietqr.io/image/MB-0369682734-compact2.png',
            'cart_snapshot' => [
                [
                    'product_id' => $this->product->id,
                    'variant_id' => $this->variant->id,
                    'product_name' => $this->product->name,
                    'price' => $amount / max(1, $qty),
                    'quantity' => $qty,
                    'subtotal' => $amount,
                ],
            ],
            'order_data' => [
                'receiver_name' => $this->customer->name,
                'receiver_phone' => '0988776655',
                'shipping_address' => 'Số 123 Giải Phóng, Hà Nội',
                'subtotal' => $amount,
                'total_amount' => $amount,
                'payment_method' => 'bank_transfer',
            ],
            'status' => $status,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function test_webhook_rejects_missing_or_invalid_auth_token(): void
    {
        $payload = [
            'id' => 10001,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'subAccount' => null,
            'transferType' => 'in',
            'transferAmount' => 200000,
            'accumulated' => 200000,
            'code' => null,
            'content' => 'HM123456 thanh toan',
            'referenceCode' => 'MB10001',
        ];

        // 1. Không gửi header auth -> 401
        $resNoAuth = $this->postJson(route('payment.webhook'), $payload);
        $resNoAuth->assertStatus(401);

        // 2. Gửi token sai -> 401
        $resWrongAuth = $this->withHeader('Authorization', 'Apikey wrong_token')
            ->postJson(route('payment.webhook'), $payload);
        $resWrongAuth->assertStatus(401);
    }

    public function test_webhook_accepts_apikey_bearer_and_raw_authorization_headers(): void
    {
        $formats = [
            'Apikey '.$this->webhookToken,
            'Bearer '.$this->webhookToken,
            $this->webhookToken, // Raw token
        ];

        foreach ($formats as $index => $authHeader) {
            $code = 'HM10000'.($index + 1);
            $session = $this->createPaymentSession($code, 200000);

            $payload = [
                'id' => 20000 + $index,
                'gateway' => 'MBBank',
                'transactionDate' => now()->format('Y-m-d H:i:s'),
                'accountNumber' => '0369682734',
                'transferType' => 'in',
                'transferAmount' => 200000,
                'content' => 'Thanh toan don '.$code,
                'referenceCode' => 'REF_'.$code,
            ];

            $response = $this->withHeader('Authorization', $authHeader)
                ->postJson(route('payment.webhook'), $payload);

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            $session->refresh();
            $this->assertEquals('paid', $session->status);
        }
    }

    public function test_webhook_accepts_x_api_key_header(): void
    {
        $code = 'HM200001';
        $session = $this->createPaymentSession($code, 200000);

        $payload = [
            'id' => 30001,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan '.$code,
            'referenceCode' => 'REF_30001',
        ];

        $response = $this->withHeader('X-Api-Key', $this->webhookToken)
            ->postJson(route('payment.webhook'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $session->refresh();
        $this->assertEquals('paid', $session->status);
    }

    public function test_webhook_authenticates_with_hmac_sha256(): void
    {
        $hmacSecret = 'super_secret_hmac_key_999';
        Config::set('bank.webhook_hmac_secret', $hmacSecret);

        $code = 'HM300001';
        $session = $this->createPaymentSession($code, 200000);

        $payload = [
            'id' => 40001,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan '.$code,
            'referenceCode' => 'REF_40001',
        ];

        $rawBody = json_encode($payload);
        $validSignature = hash_hmac('sha256', $rawBody, $hmacSecret);

        // 1. Chữ ký HMAC hợp lệ kèm token -> Thành công
        $response = $this->call(
            'POST',
            route('payment.webhook'),
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Apikey '.$this->webhookToken,
                'HTTP_X_SEPAY_SIGNATURE' => $validSignature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 2. Chữ ký HMAC sai -> 401
        $this->createPaymentSession('HM300002', 200000);
        $failPayload = [
            'id' => 40002,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan HM300002',
            'referenceCode' => 'REF_40002',
        ];
        $failRawBody = json_encode($failPayload);

        $failResponse = $this->call(
            'POST',
            route('payment.webhook'),
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Apikey '.$this->webhookToken,
                'HTTP_X_SEPAY_SIGNATURE' => 'invalid_signature_hash',
                'CONTENT_TYPE' => 'application/json',
            ],
            $failRawBody
        );

        $failResponse->assertStatus(401);
    }

    public function test_webhook_authenticates_with_official_sepay_timestamp_and_hmac(): void
    {
        $hmacSecret = 'sepay_official_secret_key_888';
        Config::set('bank.webhook_hmac_secret', $hmacSecret);

        $code = 'HM300003';
        $session = $this->createPaymentSession($code, 200000);

        $payload = [
            'id' => 40003,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan '.$code,
            'referenceCode' => 'REF_40003',
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) time();
        $stringToSign = "{$timestamp}.{$rawBody}";
        $computedSignature = 'sha256='.hash_hmac('sha256', $stringToSign, $hmacSecret);

        $response = $this->call(
            'POST',
            route('payment.webhook'),
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Apikey '.$this->webhookToken,
                'HTTP_X_SEPAY_SIGNATURE' => $computedSignature,
                'HTTP_X_SEPAY_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $session->refresh();
        $this->assertEquals('paid', $session->status);
    }

    public function test_webhook_rejects_expired_timestamp_replay_attack(): void
    {
        $hmacSecret = 'sepay_official_secret_key_888';
        Config::set('bank.webhook_hmac_secret', $hmacSecret);

        $code = 'HM300004';
        $this->createPaymentSession($code, 200000);

        $payload = [
            'id' => 40004,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan '.$code,
            'referenceCode' => 'REF_40004',
        ];

        $rawBody = json_encode($payload);
        // Timestamp cách đây 6 phút (360 giây > 300 giây cho phép)
        $expiredTimestamp = (string) (time() - 360);
        $stringToSign = "{$expiredTimestamp}.{$rawBody}";
        $computedSignature = 'sha256='.hash_hmac('sha256', $stringToSign, $hmacSecret);

        $response = $this->call(
            'POST',
            route('payment.webhook'),
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Apikey '.$this->webhookToken,
                'HTTP_X_SEPAY_SIGNATURE' => $computedSignature,
                'HTTP_X_SEPAY_TIMESTAMP' => $expiredTimestamp,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response->assertStatus(401);
    }

    public function test_webhook_matches_account_using_subaccount_fallback(): void
    {
        $code = 'HM400001';
        $session = $this->createPaymentSession($code, 200000);

        // Trường hợp SePay để accountNumber null hoặc mã VA nhưng gửi số tài khoản thật trong subAccount
        $payload = [
            'id' => 50001,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => null,
            'subAccount' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan '.$code,
            'referenceCode' => 'REF_50001',
        ];

        $response = $this->withHeader('Authorization', 'Apikey '.$this->webhookToken)
            ->postJson(route('payment.webhook'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $session->refresh();
        $this->assertEquals('paid', $session->status);
    }

    public function test_idempotent_duplicate_webhook_does_not_double_process(): void
    {
        $code = 'HM500001';
        $session = $this->createPaymentSession($code, 200000, 1);
        $initialStock = $this->variant->fresh()->stock_quantity;

        $payload = [
            'id' => 60001,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => 200000,
            'content' => 'Thanh toan '.$code,
            'referenceCode' => 'REF_60001',
        ];

        // Lần 1: Nhận Webhook và xử lý thành công
        $res1 = $this->withHeader('Authorization', 'Apikey '.$this->webhookToken)
            ->postJson(route('payment.webhook'), $payload);
        $res1->assertStatus(200);

        $orderCount = Order::where('user_id', $this->customer->id)->count();
        $this->assertEquals(1, $orderCount);

        // Tồn kho bị trừ 1
        $this->assertEquals($initialStock - 1, $this->variant->fresh()->stock_quantity);

        // Lần 2: Webhook gửi lại cùng transaction id / reference
        $res2 = $this->withHeader('Authorization', 'Apikey '.$this->webhookToken)
            ->postJson(route('payment.webhook'), $payload);
        $res2->assertStatus(200);

        // Đảm bảo không tạo thêm đơn hàng thứ hai
        $this->assertEquals(1, Order::where('user_id', $this->customer->id)->count());

        // Đảm bảo không trừ tồn kho lần thứ 2
        $this->assertEquals($initialStock - 1, $this->variant->fresh()->stock_quantity);

        // Chỉ có 1 bản ghi giao dịch ngân hàng
        $this->assertEquals(1, BankTransaction::where('transaction_code', 'REF_60001')->count());
    }

    public function test_deposit_order_has_proper_cod_remaining_and_valid_status(): void
    {
        // Giả lập đơn cọc 40%: Tổng đơn 500.000đ, cọc 200.000đ, COD còn lại 300.000đ
        $totalAmount = 500000;
        $depositAmount = 200000;
        $code = 'HM600001';

        $session = BankPaymentSession::create([
            'session_code' => $code,
            'user_id' => $this->customer->id,
            'bank_id' => 'MB',
            'bank_name' => 'Ngân hàng Quân Đội (MB Bank)',
            'account_number' => '0369682734',
            'account_name' => 'CUA HANG HUONG MIT',
            'amount' => $depositAmount, // Cần cọc 200.000
            'transfer_content' => $code,
            'qr_url' => 'https://img.vietqr.io/image/MB-0369682734-compact2.png',
            'status' => 'pending',
            'cart_snapshot' => [
                [
                    'product_id' => $this->product->id,
                    'variant_id' => $this->variant->id,
                    'product_name' => $this->product->name,
                    'price' => $totalAmount,
                    'quantity' => 1,
                    'subtotal' => $totalAmount,
                ],
            ],
            'order_data' => [
                'receiver_name' => $this->customer->name,
                'receiver_phone' => '0988776655',
                'shipping_address' => 'Số 123 Giải Phóng, Hà Nội',
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'payment_method' => 'cod',
                'is_deposit' => true,
                'deposit_amount' => $depositAmount,
            ],
            'expires_at' => now()->addMinutes(10),
        ]);

        $payload = [
            'id' => 70001,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '0369682734',
            'transferType' => 'in',
            'transferAmount' => $depositAmount,
            'content' => 'Coc 40% cho don '.$code,
            'referenceCode' => 'REF_70001',
        ];

        $response = $this->withHeader('Authorization', 'Apikey '.$this->webhookToken)
            ->postJson(route('payment.webhook'), $payload);

        $response->assertStatus(200);

        $order = Order::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);

        // Trạng thái đơn hàng phải là 1 trong 2 trạng thái hợp lệ: 'pending' (Chưa xác nhận) hoặc 'confirmed' (Đã xác nhận)
        $this->assertContains($order->order_status, ['pending', 'confirmed']);
        $this->assertEquals(300000, (float) $order->remaining_amount);
        $this->assertEquals(200000, (float) $order->paid_amount);

        // Bản ghi payment phải ghi nhận số tiền cọc
        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals($depositAmount, (float) $payment->amount);
        $this->assertEquals('deposit', $payment->payment_type);
    }
}
