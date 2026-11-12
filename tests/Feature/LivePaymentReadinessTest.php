<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankPaymentSession;
use App\Models\BankTransaction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CodeSequence;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReceipt;
use App\Models\User;
use App\Services\ConcurrencyLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LivePaymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected Category $category;

    protected Product $product;

    protected ProductVariant $variant;

    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('bank.webhook_token', 'live_ready_webhook_token');
        Config::set('bank.webhook_hmac_secret', null);
        Config::set('bank.account_number', '0988888888');

        $this->customer = User::factory()->create([
            'name' => 'Khách Hàng Test',
            'email' => 'live_customer@haomilk.test',
            'role' => 'customer',
            'password' => Hash::make('Password123!'),
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Đối Soát',
            'email' => 'live_admin@haomilk.test',
            'role' => 'admin',
            'password' => Hash::make('AdminPass123!'),
        ]);

        $this->category = Category::create(['name' => 'Sữa mẹ & bé']);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sữa Aptamil Test',
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
            'receiver_name' => 'Khách Hàng Test',
            'receiver_phone' => '0912345678',
            'province' => 'Hà Nội',
            'detail_address' => 'Số 10 Cầu Giấy',
            'is_default' => true,
        ]);
    }

    protected function createSession(
        string $sessionCode,
        string $transferContent,
        float $amount = 500000,
        int $qty = 1,
        string $status = 'pending',
        ?\DateTimeInterface $expiresAt = null
    ): BankPaymentSession {
        return BankPaymentSession::create([
            'session_code' => $sessionCode,
            'user_id' => $this->customer->id,
            'amount' => $amount,
            'bank_id' => 'MB',
            'bank_name' => 'MB Bank',
            'account_number' => '0988888888',
            'account_name' => 'HUONG MIT',
            'transfer_content' => $transferContent,
            'qr_url' => 'https://img.vietqr.io/test.png',
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
                'receiver_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'subtotal' => $amount,
                'total_amount' => $amount,
                'payment_method' => 'bank_transfer',
            ],
            'status' => $status,
            'expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);
    }

    public function test_unconfirmed_real_bank_inflows_are_persisted_for_admin_reconciliation(): void
    {
        $headers = ['Authorization' => 'Apikey live_ready_webhook_token'];

        $underpaidSession = $this->createSession('PAY-U1', 'HM100001', 500000);
        $expiredSession = $this->createSession('PAY-E1', 'HM100002', 500000, 1, 'pending', now()->subMinutes(5));
        $cancelledSession = $this->createSession('PAY-C1', 'HM100003', 500000, 1, 'cancelled');
        $oosSession = $this->createSession('PAY-OOS1', 'HM100004', 500000, 99);

        // 1. Chuyển thiếu tiền (300k / 500k)
        $this->postJson(route('payment.webhook'), [
            'id' => 101,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'MBVCB HM100001 thieu tien',
            'transferAmount' => 300000,
            'referenceCode' => 'FT-UNDER-01',
        ], $headers)->assertStatus(200)->assertJson(['success' => false]);

        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-UNDER-01',
            'transfer_code' => 'HM100001',
            'bank_payment_session_id' => $underpaidSession->id,
            'expected_amount' => 500000.00,
            'received_amount' => 300000.00,
            'difference_amount' => -200000.00,
            'status' => 'underpaid',
            'reconciliation_status' => 'needs_review',
        ]);

        // 2. Chuyển trễ sau hết hạn QR
        $this->postJson(route('payment.webhook'), [
            'id' => 102,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tre HM100002',
            'transferAmount' => 500000,
            'referenceCode' => 'FT-LATE-02',
        ], $headers)->assertStatus(200)->assertJson(['success' => false]);

        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-LATE-02',
            'transfer_code' => 'HM100002',
            'status' => 'expired_late',
            'reconciliation_status' => 'needs_review',
        ]);

        // 3. Chuyển vào phiên đã hủy
        $this->postJson(route('payment.webhook'), [
            'id' => 103,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen nham phien huy HM100003',
            'transferAmount' => 500000,
            'referenceCode' => 'FT-CANCEL-03',
        ], $headers)->assertStatus(200)->assertJson(['success' => false]);

        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-CANCEL-03',
            'status' => 'session_cancelled',
            'reconciliation_status' => 'needs_review',
        ]);

        // 4. Chuyển không chứa mã HM hoặc mã không tồn tại
        $this->postJson(route('payment.webhook'), [
            'id' => 104,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien sua khong ghi ma',
            'transferAmount' => 250000,
            'referenceCode' => 'FT-NOHM-04',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $this->postJson(route('payment.webhook'), [
            'id' => 105,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen nham ma HM999999',
            'transferAmount' => 200000,
            'referenceCode' => 'FT-MISSING-05',
        ], $headers)->assertStatus(200)->assertJson(['success' => false]);

        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-NOHM-04',
            'status' => 'unmatched_code',
            'received_amount' => 250000.00,
            'reconciliation_status' => 'needs_review',
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-MISSING-05',
            'transfer_code' => 'HM999999',
            'status' => 'unmatched_code',
            'reconciliation_status' => 'needs_review',
        ]);

        // 5. Tiền về nhưng hết tồn kho khi chốt đơn -> không tạo đơn, vẫn ghi sổ đối soát!
        $this->postJson(route('payment.webhook'), [
            'id' => 106,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen khoan HM100004',
            'transferAmount' => 500000,
            'referenceCode' => 'FT-OOS-06',
        ], $headers)->assertStatus(200)->assertJson(['success' => false]);

        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-OOS-06',
            'status' => 'out_of_stock',
            'reconciliation_status' => 'needs_review',
        ]);

        $this->assertEquals(0, Order::count());
        $this->assertEquals(10, $this->variant->fresh()->stock_quantity);

        // Customer không truy cập được trang đối soát Admin
        $this->actingAs($this->customer)
            ->get(route('admin.bank-transactions.index'))
            ->assertRedirect(route('home'));

        // Admin xem danh sách và đánh dấu đã đối soát giao dịch chuyển thiếu
        $underpaidTx = BankTransaction::where('transaction_code', 'FT-UNDER-01')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.bank-transactions.index', ['reconciliation' => 'needs_review']))
            ->assertStatus(200)
            ->assertSee('FT-UNDER-01')
            ->assertSee('HM100001')
            ->assertSee('FT-LATE-02')
            ->assertSee('FT-NOHM-04');

        $this->actingAs($this->admin)
            ->put(route('admin.bank-transactions.reconcile', $underpaidTx), [
                'reconciliation_status' => 'resolved',
                'note' => 'Đã liên hệ hoàn lại 300k cho khách qua MB Bank.',
            ])->assertRedirect();

        $underpaidTx->refresh();
        $this->assertEquals('resolved', $underpaidTx->reconciliation_status);
        $this->assertEquals($this->admin->id, $underpaidTx->reconciled_by);
        $this->assertNotNull($underpaidTx->reconciled_at);
    }

    public function test_overpayment_and_second_transfer_to_paid_session_record_reconciliation_without_second_order(): void
    {
        $headers = ['Authorization' => 'Apikey live_ready_webhook_token'];
        $session = $this->createSession('PAY-OVER2', 'HM200001', 500000, 1);

        // Chuyển thừa 75.000đ -> tạo 1 đơn 500k + ghi sổ overpaid cần đối soát hoàn 75k
        $this->postJson(route('payment.webhook'), [
            'id' => 201,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen du HM200001',
            'transferAmount' => 575000,
            'referenceCode' => 'FT-OVER-201',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $order = Order::firstOrFail();
        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-OVER-201',
            'order_id' => $order->id,
            'expected_amount' => 500000.00,
            'received_amount' => 575000.00,
            'difference_amount' => 75000.00,
            'status' => 'overpaid',
            'reconciliation_status' => 'needs_review',
        ]);

        // Khách lỡ chuyển khoản thêm LẦN 2 (mã giao dịch ngân hàng mới FT-DUP-202) vào đúng phiên HM200001
        $this->postJson(route('payment.webhook'), [
            'id' => 202,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen nham lan 2 HM200001',
            'transferAmount' => 500000,
            'referenceCode' => 'FT-DUP-202',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals(1, Order::count());
        $this->assertEquals(9, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('bank_transactions', [
            'transaction_code' => 'FT-DUP-202',
            'order_id' => $order->id,
            'received_amount' => 500000.00,
            'status' => 'duplicate_tx',
            'reconciliation_status' => 'needs_review',
        ]);
    }

    public function test_duplicate_and_late_webhooks_remain_idempotent_after_cache_flush_and_restart(): void
    {
        $headers = ['Authorization' => 'Apikey live_ready_webhook_token'];
        $this->createSession('PAY-RST1', 'HM300001', 500000, 2);
        $otherSession = $this->createSession('PAY-RST2', 'HM300002', 500000, 1);

        $payload = [
            'id' => 3001,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM300001',
            'transferAmount' => 500000,
            'referenceCode' => 'FT-RESTART-3001',
        ];

        $this->postJson(route('payment.webhook'), $payload, $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Xóa sạch cache mô phỏng server restart / flush cache trên hosting
        Cache::flush();

        // SePay gửi lại cùng webhook sau khi restart -> idempotent 200, KHÔNG tạo thêm đơn/payment/bank_transaction
        $this->postJson(route('payment.webhook'), $payload, $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(1, Order::count());
        $this->assertEquals(1, Payment::where('transaction_code', 'FT-RESTART-3001')->count());
        $this->assertEquals(1, BankTransaction::where('transaction_code', 'FT-RESTART-3001')->count());
        $this->assertEquals(8, $this->variant->fresh()->stock_quantity);

        // Replay cùng referenceCode cho phiên khác sau khi xóa cache vẫn bị chặn 409
        $this->postJson(route('payment.webhook'), [
            'id' => 3002,
            'transferType' => 'in',
            'accountNumber' => '0988888888',
            'content' => 'Chuyen tien HM300002',
            'transferAmount' => 500000,
            'referenceCode' => 'FT-RESTART-3001',
        ], $headers)->assertStatus(409)->assertJson(['success' => false]);

        $this->assertEquals('pending', $otherSession->fresh()->status);
    }

    public function test_checkout_idempotency_and_lock_survive_cache_flush_and_different_cache_drivers(): void
    {
        $this->variant->update(['price' => 200000, 'stock_quantity' => 10]);

        $cart = Cart::create(['user_id' => $this->customer->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'price' => 200000,
        ]);

        // Chạy qua file cache driver và flush cache giữa 2 request
        Config::set('cache.default', 'file');
        Cache::store('file')->flush();

        $idempotencyKey = 'idem-cod-'.uniqid();
        $payload = [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
            'idempotency_key' => $idempotencyKey,
        ];

        $res1 = $this->actingAs($this->customer)->post(route('checkout.process'), $payload);
        $order = Order::where('user_id', $this->customer->id)->firstOrFail();
        $res1->assertRedirect(route('checkout.success', ['order_code' => $order->order_code]));
        $this->assertEquals($idempotencyKey, $order->idempotency_key);

        // Thêm sản phẩm khác vào giỏ + xóa sạch cache -> submit lại ĐÚNG idempotency_key cũ -> trả lại đúng đơn cũ!
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'price' => 200000,
        ]);
        Cache::flush();

        $res2 = $this->actingAs($this->customer)->post(route('checkout.process'), $payload);
        $res2->assertRedirect(route('checkout.success', ['order_code' => $order->order_code]));
        $this->assertEquals(1, Order::count());
        $this->assertEquals(9, $this->variant->fresh()->stock_quantity);

        // Kiểm tra bank_transfer > 400k idempotency sau khi xóa cache không tạo session thứ 2 và không hủy session đang chờ
        $this->variant->update(['price' => 500000]);
        $cart->items()->delete();
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'price' => 500000,
        ]);

        $bankIdemKey = 'idem-bank-'.uniqid();
        $bankPayload = [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
            'idempotency_key' => $bankIdemKey,
        ];

        $b1 = $this->actingAs($this->customer)->post(route('checkout.process'), $bankPayload);
        $session = BankPaymentSession::where('idempotency_key', $bankIdemKey)->firstOrFail();
        $b1->assertRedirect(route('checkout.bank-payment', ['session_code' => $session->session_code]));

        Cache::flush();
        $executed = ConcurrencyLock::run('test_hosting_lock_compat', 5, 2, fn (): string => 'locked_ok');
        $this->assertEquals('locked_ok', $executed);

        $b2 = $this->actingAs($this->customer)->post(route('checkout.process'), $bankPayload);
        $b2->assertRedirect(route('checkout.bank-payment', ['session_code' => $session->session_code]));
        $this->assertEquals(1, BankPaymentSession::count());
        $this->assertEquals('pending', $session->fresh()->status);

        $this->assertNotNull($cartItem);
    }

    public function test_migration_and_code_sequences_handle_legacy_data_gaps_and_over_9999(): void
    {
        // Mô phỏng dữ liệu kiểu haomilk cũ: orders có khoảng trống ID (id=9 -> DH0007), stock_receipts (id=7 -> PN0006)
        $order = Order::create([
            'order_code' => 'DH0007',
            'user_id' => $this->customer->id,
            'receiver_name' => 'Legacy',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'subtotal' => 100000,
            'total_amount' => 100000,
        ]);
        $this->assertNotNull($order);

        StockReceipt::create([
            'code' => 'PN0006',
            'user_id' => $this->admin->id,
            'supplier_name' => 'Nhà cung cấp Legacy',
            'receipt_date' => now()->toDateString(),
            'total_quantity' => 1,
            'total_amount' => 100000,
            'status' => 'completed',
        ]);

        // Giả lập bảng code_sequences đang bị thấp hơn hoặc reset và chạy lại logic migration 2026_11_12_100000
        DB::table('code_sequences')->delete();
        $migration = require database_path('migrations/2026_11_12_100000_add_concurrency_sequences_and_status_fixes.php');
        $migration->up();

        $this->assertEquals(7, (int) DB::table('code_sequences')->where('name', 'orders')->value('current_number'));
        $this->assertEquals(6, (int) DB::table('code_sequences')->where('name', 'stock_receipts')->value('current_number'));

        $this->assertEquals('DH0008', Order::generateOrderCode());
        $this->assertEquals('PN0007', StockReceipt::getNextCode());
        $this->assertEquals('PN0007', StockReceipt::allocateNextCode());

        // Vượt ngưỡng 9999 -> DH10000 không bị cắt/trùng
        CodeSequence::where('name', 'orders')->update(['current_number' => 9999]);
        $this->assertEquals('DH10000', Order::generateOrderCode());
        $this->assertEquals('DH10001', Order::generateOrderCode());
    }

    public function test_admin_cod_delivered_ui_flow_and_deposit_history_integrity(): void
    {
        $order = Order::create([
            'order_code' => 'DH0120',
            'user_id' => $this->customer->id,
            'receiver_name' => 'Khách Cọc 40%',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Số 10 Cầu Giấy, Hà Nội',
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'payment_method' => 'cod',
            'payment_status' => 'deposit_paid',
            'deposit_required' => true,
            'deposit_amount' => 400000,
            'paid_amount' => 400000,
            'remaining_amount' => 600000,
            'order_status' => 'confirmed',
        ]);

        $depositPayment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'deposit',
            'payment_method' => 'bank_transfer',
            'amount' => 400000,
            'status' => 'paid',
            'transaction_code' => 'FT-DEPOSIT-400K',
            'paid_at' => now()->subHour(),
            'confirmed_at' => now()->subHour(),
            'note' => 'Thanh toán tiền cọc 40% cho đơn hàng COD qua mã VietQR thành công',
        ]);

        // Giao diện chi tiết đơn đã xác nhận PHẢI có nút Đánh dấu Giao thành công
        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertStatus(200)
            ->assertSee('Đánh dấu Giao thành công')
            ->assertSee('FT-DEPOSIT-400K')
            ->assertSee('400.000 đ');

        // Admin bấm Đánh dấu Giao thành công
        $this->actingAs($this->admin)
            ->put(route('admin.orders.update-status', $order), [
                'order_status' => 'delivered',
            ])->assertRedirect();

        $order->refresh();
        $depositPayment->refresh();

        $this->assertEquals('delivered', $order->order_status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(400000.00, (float) $order->deposit_amount);
        $this->assertEquals(1000000.00, (float) $order->paid_amount);
        $this->assertEquals(0.0, (float) $order->remaining_amount);

        // Lịch sử cọc ban đầu giữ nguyên 100%, không bị ghi đè thành 1.000.000 đ hay mất transaction_code
        $this->assertEquals('paid', $depositPayment->status);
        $this->assertEquals(400000.00, (float) $depositPayment->amount);
        $this->assertEquals('FT-DEPOSIT-400K', $depositPayment->transaction_code);

        // Bản ghi thu COD cuối kỳ 600k tồn tại song song
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_type' => 'final',
            'payment_method' => 'cod',
            'amount' => 600000.00,
            'status' => 'paid',
        ]);

        // Trang admin chi tiết, in đơn, danh sách lọc delivered và trang khách hàng đều hiển thị ĐÚNG 400.000 đ tiền cọc & Giao thành công
        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertStatus(200)
            ->assertSee('Giao thành công')
            ->assertSee('-400.000 đ')
            ->assertSee('-600.000 đ');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.print', $order))
            ->assertStatus(200)
            ->assertSee('-400.000 đ');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'delivered']))
            ->assertStatus(200)
            ->assertSee('DH0120');

        $this->actingAs($this->customer)
            ->get(route('account.orders.show', $order->order_code))
            ->assertStatus(200)
            ->assertSee('Giao thành công')
            ->assertSee('-400.000 đ')
            ->assertSee('-600.000 đ');

        // Nếu Admin đặt lại chưa thu phần COD (updatePaymentStatus = unpaid) -> chỉ reset phần COD cuối kỳ, TUYỆT ĐỐI KHÔNG xóa tiền cọc VietQR!
        $this->actingAs($this->admin)
            ->put(route('admin.orders.update-payment-status', $order), [
                'payment_status' => 'unpaid',
            ])->assertRedirect();

        $order->refresh();
        $depositPayment->refresh();

        $this->assertEquals('deposit_paid', $order->payment_status);
        $this->assertEquals(400000.00, (float) $order->paid_amount);
        $this->assertEquals(600000.00, (float) $order->remaining_amount);
        $this->assertEquals('paid', $depositPayment->status);
        $this->assertEquals('FT-DEPOSIT-400K', $depositPayment->transaction_code);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_type' => 'final',
            'payment_method' => 'cod',
            'status' => 'pending',
        ]);
    }
}
