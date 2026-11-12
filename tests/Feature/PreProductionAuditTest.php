<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankPaymentSession;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleCampaign;
use App\Models\SaleCampaignItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PreProductionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected User $attacker;

    protected Category $category;

    protected Product $product;

    protected ProductVariant $variant;

    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('bank.webhook_token', 'test_sepay_webhook_secret_token');
        Config::set('bank.webhook_hmac_secret', null);
        Config::set('bank.account_number', '0988888888');

        $this->customer = User::factory()->create([
            'name' => 'Nguyễn Thị Khách',
            'email' => 'customer@haomilk.test',
            'role' => 'customer',
            'password' => Hash::make('Password123!'),
        ]);

        $this->attacker = User::factory()->create([
            'name' => 'Kẻ Tấn Công',
            'email' => 'attacker@evil.test',
            'role' => 'customer',
            'password' => Hash::make('Password123!'),
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Quản Trị Viên',
            'email' => 'admin@haomilk.test',
            'role' => 'admin',
            'password' => Hash::make('AdminPass123!'),
        ]);

        $this->category = Category::create(['name' => 'Sữa bột']);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sữa Meiji Số 9',
            'base_price' => 500000,
            'status' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 500000,
            'stock_quantity' => 10,
        ]);

        $this->address = Address::create([
            'user_id' => $this->customer->id,
            'receiver_name' => 'Nguyễn Thị Khách',
            'receiver_phone' => '0912345678',
            'province' => 'Hà Nội',
            'detail_address' => 'Số 1 Đống Đa',
            'is_default' => true,
        ]);
    }

    /**
     * Helper tạo BankPaymentSession mẫu dùng trong các test thanh toán/webhook.
     */
    protected function createPendingSession(
        string $sessionCode,
        string $transferContent,
        float $amount = 500000,
        int $quantity = 1,
        ?\DateTimeInterface $expiresAt = null
    ): BankPaymentSession {
        return BankPaymentSession::create([
            'session_code' => $sessionCode,
            'user_id' => $this->customer->id,
            'amount' => $amount,
            'bank_id' => 'MB',
            'bank_name' => 'MBBank',
            'account_number' => '0988888888',
            'account_name' => 'HUONG MIT',
            'transfer_content' => $transferContent,
            'qr_url' => 'https://vietqr.io/test',
            'cart_snapshot' => [
                [
                    'product_id' => $this->product->id,
                    'variant_id' => $this->variant->id,
                    'product_name' => $this->product->name,
                    'price' => $amount / max(1, $quantity),
                    'quantity' => $quantity,
                    'subtotal' => $amount,
                ],
            ],
            'order_data' => [
                'receiver_name' => $this->customer->name,
                'receiver_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'subtotal' => $amount,
                'total_amount' => $amount,
                'payment_method' => 'bank_transfer',
            ],
            'status' => 'pending',
            'expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SEC-01 (P0): SEPAY WEBHOOK AUTHENTICATION & FAIL-CLOSED REGRESSION
    // ════════════════════════════════════════════════════════════════════════

    public function test_sec_01_webhook_rejects_missing_authorization_header(): void
    {
        $session = $this->createPendingSession('PAY-SEC01A', 'HM111111');

        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Giao dich chuyen tien HM111111',
            'transferAmount' => 500000,
            'referenceCode' => 'FAKE-TXN-001',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('pending', $session->fresh()->status);
        $this->assertEquals(0, Order::count());
    }

    public function test_sec_01_webhook_rejects_invalid_token_and_query_string_token(): void
    {
        $session = $this->createPendingSession('PAY-SEC01B', 'HM111112');

        // 1. Sai token trong header
        $wrongRes = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM111112',
            'transferAmount' => 500000,
            'referenceCode' => 'FAKE-TXN-002',
        ], [
            'Authorization' => 'Apikey wrong_secret_token',
        ]);
        $wrongRes->assertStatus(401);

        // 2. Gửi token đúng qua URL query string ?token=... nhưng thiếu Header -> PHẢI từ chối
        $queryRes = $this->postJson(route('payment.webhook').'?token=test_sepay_webhook_secret_token', [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM111112',
            'transferAmount' => 500000,
            'referenceCode' => 'FAKE-TXN-003',
        ]);
        $queryRes->assertStatus(401);

        // 3. Gửi qua body token nhưng thiếu Header -> PHẢI từ chối
        $bodyRes = $this->postJson(route('payment.webhook'), [
            'token' => 'test_sepay_webhook_secret_token',
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM111112',
            'transferAmount' => 500000,
        ]);
        $bodyRes->assertStatus(401);

        $this->assertEquals('pending', $session->fresh()->status);
        $this->assertEquals(0, Order::count());
    }

    public function test_sec_01_webhook_fails_closed_when_secrets_not_configured(): void
    {
        Config::set('bank.webhook_token', null);
        Config::set('bank.webhook_hmac_secret', null);

        $session = $this->createPendingSession('PAY-SEC01C', 'HM111113');

        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM111113',
            'transferAmount' => 500000,
            'referenceCode' => 'FAKE-TXN-004',
        ]);

        $response->assertStatus(500);
        $this->assertEquals('pending', $session->fresh()->status);
        $this->assertEquals(0, Order::count());
    }

    public function test_sec_01_webhook_hmac_sha256_accepts_valid_and_rejects_tampered_payload(): void
    {
        Config::set('bank.webhook_token', null);
        Config::set('bank.webhook_hmac_secret', 'hmac_super_secret_2026');

        $session = $this->createPendingSession('PAY-SEC01D', 'HM111114');

        $payloadArray = [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM111114',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-HMAC-001',
        ];
        $rawJson = json_encode($payloadArray, JSON_UNESCAPED_UNICODE);
        $validSig = hash_hmac('sha256', (string) $rawJson, 'hmac_super_secret_2026');

        // Payload bị sửa đổi số tiền nhưng giữ chữ ký cũ -> PHẢI bị từ chối 401
        $tamperedPayload = json_encode(array_merge($payloadArray, ['transferAmount' => 999999]));
        $tamperRes = $this->call('POST', route('payment.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SEPAY_SIGNATURE' => $validSig,
        ], (string) $tamperedPayload);

        $tamperRes->assertStatus(401);
        $this->assertEquals('pending', $session->fresh()->status);

        // Chữ ký hợp lệ trên raw body chuẩn -> 200 OK & hoàn tất đơn
        $validRes = $this->call('POST', route('payment.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SEPAY_SIGNATURE' => $validSig,
        ], (string) $rawJson);

        $validRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('paid', $session->fresh()->status);
        $this->assertEquals(1, Order::count());
    }

    public function test_sec_01_webhook_does_not_log_sensitive_auth_tokens(): void
    {
        Log::spy();
        $session = $this->createPendingSession('PAY-SEC01E', 'HM111115');

        $secret = 'test_sepay_webhook_secret_token';
        $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => "Chuyen {$session->transfer_content}",
            'transferAmount' => 500000,
            'referenceCode' => 'MB-LOG-001',
        ], [
            'Authorization' => "Apikey {$secret}",
        ])->assertStatus(200);

        Log::shouldNotHaveReceived('info', function ($message, $context = []) use ($secret) {
            return str_contains(json_encode([$message, $context]), $secret);
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SEC-02 (P0): SIMULATION ENDPOINT DISABLED IN STAGING & PRODUCTION
    // ════════════════════════════════════════════════════════════════════════

    public function test_sec_02_simulate_endpoint_blocked_in_production_and_staging_even_with_debug_true(): void
    {
        $session = $this->createPendingSession('PAY-SEC02', 'HM222222');

        foreach (['production', 'staging'] as $envName) {
            Config::set('app.env', $envName);
            Config::set('app.debug', true);

            $response = $this->actingAs($this->customer)
                ->post("/thanh-toan/chuyen-khoan/{$session->session_code}/gia-lap-thanh-cong");

            $response->assertStatus(404);
        }

        $this->assertEquals('pending', $session->fresh()->status);
        $this->assertEquals(0, Order::count());
        $this->assertEquals(10, $this->variant->fresh()->stock_quantity);
    }

    public function test_sec_02_valid_sepay_webhook_still_works_when_app_env_is_production(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', false);

        $session = $this->createPendingSession('PAY-SEC02B', 'HM222223');

        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM222223',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-PROD-001',
        ], [
            'Authorization' => 'Apikey test_sepay_webhook_secret_token',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('paid', $session->fresh()->status);
        $this->assertEquals(1, Order::count());
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SEC-03, SEC-04, SEC-05 (P1): IDOR & ACCOUNT LOCKOUT + LAYERED DEFENSE
    // ════════════════════════════════════════════════════════════════════════

    public function test_sec_03_idor_user_cannot_view_another_users_order(): void
    {
        $orderB = Order::create([
            'order_code' => 'DH0099',
            'user_id' => $this->customer->id,
            'receiver_name' => 'Khách B',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'subtotal' => 500000,
            'total_amount' => 500000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
        ]);

        $this->actingAs($this->attacker)
            ->get(route('account.orders.show', ['order_code' => $orderB->order_code]))
            ->assertStatus(404);
    }

    public function test_sec_04_idor_user_cannot_delete_another_users_address(): void
    {
        $addressB = Address::create([
            'user_id' => $this->customer->id,
            'receiver_name' => 'Khách B',
            'receiver_phone' => '0912345678',
            'province' => 'Hà Nội',
            'detail_address' => 'Địa chỉ Khách B',
            'is_default' => false,
        ]);

        $this->actingAs($this->attacker)
            ->delete(route('account.addresses.destroy', ['address' => $addressB->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('addresses', ['id' => $addressB->id]);
    }

    public function test_sec_05_account_lockout_and_ip_rate_limit_work_together(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('auth.login.post'), [
                'email' => $this->customer->email,
                'password' => 'WrongPassword!',
            ]);
        }

        $this->customer->refresh();
        $this->assertTrue($this->customer->isLocked());

        // Ngay sau 5 lần sai trong cùng phút -> bị chặn trước bởi Rate Limiter (429)
        $immediateResponse = $this->post(route('auth.login.post'), [
            'email' => $this->customer->email,
            'password' => 'Password123!',
        ]);
        $immediateResponse->assertStatus(429);
        $this->assertFalse(auth()->check());

        // Sau 65 giây (hết cửa sổ rate limit 1 phút nhưng còn trong 15 phút khóa DB) -> khóa DB vẫn chặn dù đúng mật khẩu
        $this->travel(65)->seconds();
        $lockedResponse = $this->post(route('auth.login.post'), [
            'email' => $this->customer->email,
            'password' => 'Password123!',
        ]);
        $lockedResponse->assertSessionHasErrors('email');
        $this->assertFalse(auth()->check());
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SEC-06 (P1): ADMIN PRIVILEGE ESCALATION PROTECTION
    // ════════════════════════════════════════════════════════════════════════

    public function test_sec_06_admin_customer_controller_blocks_mutations_on_admin_accounts(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@haomilk.test',
            'role' => 'admin',
            'password' => Hash::make('SuperSecretPassword123!'),
        ]);

        // 1. Admin quản lý customer hợp lệ -> Thành công
        $this->actingAs($this->admin)
            ->post(route('admin.customers.toggle-lock', ['customer' => $this->customer->id]))
            ->assertRedirect();
        $this->assertTrue($this->customer->fresh()->isLocked());

        // 2. Admin KHÔNG thể reset mật khẩu Admin khác
        $this->actingAs($this->admin)
            ->post(route('admin.customers.reset-password', ['customer' => $superAdmin->id]), [
                'password' => 'HackedPassword123!',
            ])
            ->assertStatus(403);
        $this->assertTrue(Hash::check('SuperSecretPassword123!', $superAdmin->fresh()->password));

        // 3. Admin KHÔNG thể khóa / xem / sửa Admin khác qua CustomerController
        $this->actingAs($this->admin)
            ->post(route('admin.customers.toggle-lock', ['customer' => $superAdmin->id]))
            ->assertStatus(403);
        $this->assertFalse($superAdmin->fresh()->isLocked());

        $this->actingAs($this->admin)
            ->get(route('admin.customers.show', ['customer' => $superAdmin->id]))
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->put(route('admin.customers.update', ['customer' => $superAdmin->id]), [
                'name' => 'Hijacked Name',
                'email' => 'hijacked@evil.test',
            ])
            ->assertStatus(403);
        $this->assertEquals('superadmin@haomilk.test', $superAdmin->fresh()->email);

        // 4. Customer KHÔNG thể truy cập endpoint Admin (web redirect về home, API trả về 403, mật khẩu giữ nguyên)
        $this->actingAs($this->customer)
            ->post(route('admin.customers.reset-password', ['customer' => $superAdmin->id]), [
                'password' => 'HackedPassword123!',
            ])
            ->assertRedirect(route('home'));

        $this->actingAs($this->customer)
            ->postJson(route('admin.customers.reset-password', ['customer' => $superAdmin->id]), [
                'password' => 'HackedPassword123!',
            ])
            ->assertStatus(403);

        $this->assertTrue(Hash::check('SuperSecretPassword123!', $superAdmin->fresh()->password));
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PAY-01..05 & SEC-07 (P0/P1): WEBHOOK VALIDATION, REPLAY & RECONCILIATION
    // ════════════════════════════════════════════════════════════════════════

    public function test_pay_01_duplicate_and_replay_webhook_are_idempotent_and_replay_blocked(): void
    {
        $session1 = $this->createPendingSession('PAY-TEST03', 'HM333333', 1000000, 2);
        $session2 = $this->createPendingSession('PAY-TEST03B', 'HM333334', 500000, 1);

        $headers = [
            'Authorization' => 'Apikey test_sepay_webhook_secret_token',
        ];

        // Lần 1: Webhook hợp lệ cho HM333333
        $res1 = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM333333',
            'transferAmount' => 1000000,
            'referenceCode' => 'MB-TXN-101',
        ], $headers);

        $res1->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(8, $this->variant->fresh()->stock_quantity);

        // Lần 2: SePay retry gửi lại chính giao dịch đó (Idempotent)
        $res2 = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM333333',
            'transferAmount' => 1000000,
            'referenceCode' => 'MB-TXN-101',
        ], $headers);

        $res2->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(8, $this->variant->fresh()->stock_quantity);
        $this->assertEquals(1, Order::where('user_id', $this->customer->id)->count());

        // Lần 3 (Replay attack): Cố tái sử dụng mã tham chiếu MB-TXN-101 cho phiên HM333334 khác
        $res3 = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM333334',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-TXN-101',
        ], $headers);

        $res3->assertStatus(409)->assertJson(['success' => false]);
        $this->assertEquals('pending', $session2->fresh()->status);
        $this->assertEquals(8, $this->variant->fresh()->stock_quantity);
    }

    public function test_pay_02_webhook_rejects_underpayment(): void
    {
        $session = $this->createPendingSession('PAY-TEST04', 'HM444444');

        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM444444',
            'transferAmount' => 300000,
            'referenceCode' => 'MB-TXN-102',
        ], [
            'Authorization' => 'Apikey test_sepay_webhook_secret_token',
        ]);

        $response->assertJson(['success' => false]);
        $this->assertEquals('pending', $session->fresh()->status);
        $this->assertEquals(10, $this->variant->fresh()->stock_quantity);
    }

    public function test_pay_03_webhook_rejects_expired_session(): void
    {
        $session = $this->createPendingSession('PAY-TEST05', 'HM555555', 500000, 1, now()->subMinutes(5));

        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM555555',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-TXN-103',
        ], [
            'Authorization' => 'Apikey test_sepay_webhook_secret_token',
        ]);

        $response->assertJson(['success' => false]);
        $this->assertEquals(0, Order::count());
        $this->assertEquals(10, $this->variant->fresh()->stock_quantity);
    }

    public function test_sec_07_webhook_verifies_destination_account_and_incoming_transfer_type(): void
    {
        $session = $this->createPendingSession('PAY-TEST06', 'HM666666');
        $headers = ['Authorization' => 'Apikey test_sepay_webhook_secret_token'];

        // 1. Sai số tài khoản thụ hưởng -> Từ chối 422
        $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '9999999999',
            'content' => 'Chuyen tien HM666666',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-TXN-104',
        ], $headers)->assertStatus(422)->assertJson(['success' => false]);

        // 2. Thiếu accountNumber -> Từ chối 422
        $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'content' => 'Chuyen tien HM666666',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-TXN-105',
        ], $headers)->assertStatus(422)->assertJson(['success' => false]);

        // 3. Giao dịch tiền ra (transferType = out) -> Từ chối 422
        $this->postJson(route('payment.webhook'), [
            'transferType' => 'out',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM666666',
            'transferAmount' => 500000,
            'referenceCode' => 'MB-TXN-106',
        ], $headers)->assertStatus(422)->assertJson(['success' => false]);

        $this->assertEquals('pending', $session->fresh()->status);
        $this->assertEquals(0, Order::count());
    }

    public function test_sec_07_webhook_handles_overpayment_with_reconciliation_note(): void
    {
        $session = $this->createPendingSession('PAY-OVER01', 'HM777777', 500000, 1);

        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM777777',
            'transferAmount' => 550000,
            'referenceCode' => 'MB-OVER-01',
        ], [
            'Authorization' => 'Apikey test_sepay_webhook_secret_token',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $payment = Payment::where('transaction_code', 'MB-OVER-01')->first();
        $this->assertNotNull($payment);
        $this->assertStringContainsString('50.000', (string) $payment->note);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ORD-01, ORD-02, RACE-01 (P0/P1): INVENTORY ATOMICITY & ORDER SETTLEMENT
    // ════════════════════════════════════════════════════════════════════════

    public function test_ord_01_and_race_01_admin_cancelling_twice_restores_stock_only_once(): void
    {
        $this->variant->update(['stock_quantity' => 5]);

        $order = Order::create([
            'order_code' => 'DH0088',
            'user_id' => $this->customer->id,
            'receiver_name' => 'Nguyễn Thị Khách',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'sku' => 'SP-0001-1',
            'variant_name' => 'Mặc định',
            'price' => 500000,
            'quantity' => 2,
            'subtotal' => 1000000,
        ]);

        // Hủy lần 1 -> hoàn +2 (5 -> 7)
        $this->actingAs($this->admin)
            ->put(route('admin.orders.update-status', $order), [
                'order_status' => 'cancelled',
                'admin_note' => 'Khách gọi điện xin hủy',
            ])->assertRedirect();

        // Hủy lần 2 (lặp lại) -> KHÔNG được cộng tồn kho thêm lần nữa
        $this->actingAs($this->admin)
            ->put(route('admin.orders.update-status', $order), [
                'order_status' => 'cancelled',
            ])->assertRedirect();

        $this->assertEquals(7, $this->variant->fresh()->stock_quantity);
        $this->assertEquals(
            1,
            InventoryTransaction::where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->where('type', 'return')
                ->count()
        );
    }

    public function test_ord_02_delivered_cod_with_deposit_clears_remaining_balance(): void
    {
        $order = Order::create([
            'order_code' => 'DH0089',
            'user_id' => $this->customer->id,
            'receiver_name' => 'Nguyễn Thị Khách',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'payment_method' => 'cod',
            'payment_status' => 'deposit_paid',
            'deposit_required' => true,
            'deposit_amount' => 400000,
            'paid_amount' => 400000,
            'remaining_amount' => 600000,
            'order_status' => 'shipping',
        ]);

        $order->payments()->create([
            'payment_type' => 'deposit',
            'payment_method' => 'bank_transfer',
            'amount' => 400000,
            'status' => 'paid',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update-status', $order), [
                'order_status' => 'delivered',
            ])->assertRedirect();

        $order->refresh();
        $this->assertEquals('delivered', $order->order_status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(1000000.00, (float) $order->paid_amount);
        $this->assertEquals(0.0, (float) $order->remaining_amount);

        // Có bản ghi payment final 600k trạng thái paid được ghi nhận
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_type' => 'final',
            'payment_method' => 'cod',
            'amount' => 600000,
            'status' => 'paid',
        ]);
    }

    public function test_race_01_checkout_blocks_overselling_when_stock_depleted_before_commit(): void
    {
        // Biến thể có đơn giá <= 400k để test trực tiếp luồng COD trừ kho ngay
        $cheapVariant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 100000,
            'stock_quantity' => 2,
        ]);

        $cart1 = Cart::create(['user_id' => $this->customer->id]);
        CartItem::create([
            'cart_id' => $cart1->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $cheapVariant->id,
            'quantity' => 2,
            'price' => 100000,
        ]);

        $customer2 = User::factory()->create(['role' => 'customer']);
        Address::create([
            'user_id' => $customer2->id,
            'receiver_name' => 'Khách 2',
            'receiver_phone' => '0987654321',
            'province' => 'Hà Nội',
            'detail_address' => 'Số 2 Ba Đình',
            'is_default' => true,
        ]);
        $cart2 = Cart::create(['user_id' => $customer2->id]);
        CartItem::create([
            'cart_id' => $cart2->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $cheapVariant->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        // Khách 1 mua hết 2 sản phẩm
        $this->actingAs($this->customer)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
        ])->assertRedirect();

        $this->assertEquals(0, $cheapVariant->fresh()->stock_quantity);

        // Khách 2 cố gắng đặt 1 sản phẩm khi tồn kho vừa hết -> phải bị từ chối, kho KHÔNG âm
        $this->actingAs($customer2)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $customer2->addresses()->first()->id,
            'payment_method' => 'cod',
        ])->assertSessionHas('error');

        $this->assertEquals(0, $cheapVariant->fresh()->stock_quantity);
        $this->assertEquals(1, Order::count());
    }

    // ════════════════════════════════════════════════════════════════════════
    //  RATE-01 & RATE-02 (P1/P2): CHATBOT, CSRF, API KEY HEADER & AUTH THROTTLE
    // ════════════════════════════════════════════════════════════════════════

    public function test_rate_01_chatbot_rate_limit_blocks_excess_and_skips_gemini_and_uses_header_key(): void
    {
        Config::set('services.gemini.api_key', 'dummy_gemini_test_key');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => "Dạ shop có sữa Meiji ạ!\n[RECOMMENDED_IDS: none]"]]],
                ]],
            ], 200),
        ]);

        // 12 request hợp lệ đầu tiên trong 1 phút -> OK
        for ($i = 1; $i <= 12; $i++) {
            $this->postJson(route('chatbot.message'), [
                'message' => "Tư vấn sữa cho bé lần {$i}",
            ])->assertStatus(200);
        }

        // Request thứ 13..15 -> 429 Too Many Requests và KHÔNG gọi thêm HTTP Gemini API
        for ($i = 13; $i <= 15; $i++) {
            $this->postJson(route('chatbot.message'), [
                'message' => "Spam lần {$i}",
            ])->assertStatus(429)->assertJson(['success' => false]);
        }

        Http::assertSentCount(12);
        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'dummy_gemini_test_key')
                && ! str_contains($request->url(), 'key=');
        });
    }

    public function test_rate_01_chatbot_enforces_csrf_outside_unit_testing_environment(): void
    {
        // Đảm bảo chatbot/* không còn nằm trong cấu hình ngoại lệ CSRF của bootstrap/app.php
        $bootstrapContent = (string) file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringNotContainsString("'chatbot/*'", $bootstrapContent);

        // Giả lập môi trường staging (non-testing) để bật kiểm tra CSRF -> request không token phải nhận 419
        $originalEnv = $this->app['env'];
        $this->app['env'] = 'staging';
        try {
            $this->postJson('/chatbot/message', ['message' => 'Xin chào'])
                ->assertStatus(419);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    public function test_rate_02_login_blocks_email_spraying_from_same_ip(): void
    {
        // Thử 10 email khác nhau từ cùng 1 IP -> vượt ngưỡng 10/phút sẽ bị chặn 429
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('auth.login.post'), [
                'email' => "spray{$i}@evil.test",
                'password' => 'WrongPassword!',
            ]);
        }

        $this->post(route('auth.login.post'), [
            'email' => 'spray11@evil.test',
            'password' => 'WrongPassword!',
        ])->assertStatus(429);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PERF-01 & CONF-01 (P2): N+1 QUERY REDUCTION & STAGING CONFIG READINESS
    // ════════════════════════════════════════════════════════════════════════

    public function test_perf_01_home_page_eliminates_variant_active_sale_item_n_plus_1_queries(): void
    {
        $campaign = SaleCampaign::create([
            'name' => 'Flash Sale Audit',
            'slug' => 'flash-sale-audit',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
        ]);

        // Tạo 12 sản phẩm × 2 biến thể = 24 biến thể bổ sung
        for ($i = 1; $i <= 12; $i++) {
            $p = Product::create([
                'category_id' => $this->category->id,
                'name' => "Sản phẩm audit {$i}",
                'base_price' => 200000,
                'status' => true,
            ]);
            for ($v = 1; $v <= 2; $v++) {
                $variant = ProductVariant::create([
                    'product_id' => $p->id,
                    'price' => 200000,
                    'stock_quantity' => 20,
                ]);
                SaleCampaignItem::create([
                    'sale_campaign_id' => $campaign->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $variant->id,
                    'original_price' => 200000,
                    'sale_price' => 150000,
                    'sale_quantity' => 10,
                    'sold_quantity' => 0,
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('home'))->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Trước tối ưu, 25+ biến thể phát sinh >= 30..60 query N+1; sau eager load chỉ cần <= 15 query cho toàn trang
        $this->assertLessThanOrEqual(
            15,
            count($queries),
            'Phát hiện N+1 query trên trang chủ: '.count($queries).' queries'
        );
    }

    public function test_conf_01_staging_and_production_env_examples_are_hardened(): void
    {
        foreach (['.env.staging.example', '.env.production.example'] as $file) {
            $path = base_path($file);
            $this->assertFileExists($path);
            $contents = (string) file_get_contents($path);
            $this->assertStringContainsString('APP_DEBUG=false', $contents);
            $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $contents);
            $this->assertStringNotContainsString('haomilk_secure_webhook_token_2026', $contents);
        }
    }
}
