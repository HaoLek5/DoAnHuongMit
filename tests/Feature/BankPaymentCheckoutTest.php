<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankPaymentSession;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BankPaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected Product $product;

    protected ProductVariant $variant;

    protected Address $address;

    protected Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('bank.webhook_token', 'test_webhook_secret_token');
        Config::set('bank.webhook_hmac_secret', null);
        Config::set('bank.account_number', '0988888888');

        $this->user = User::factory()->create([
            'role' => 'customer',
            'name' => 'Nguyễn Văn Test',
            'phone' => '0912345678',
        ]);

        $this->address = Address::create([
            'user_id' => $this->user->id,
            'receiver_name' => 'Nguyễn Văn Test',
            'receiver_phone' => '0912345678',
            'province' => 'Hà Nội',
            'district' => 'Ba Đình',
            'ward' => 'Kim Mã',
            'detail_address' => 'Số 10 Kim Mã',
            'is_default' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Sữa cho bé',
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sữa Meiji Thanh 1-3 tuổi',
            'base_price' => 250000,
            'status' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 250000,
            'stock_quantity' => 10,
        ]);

        $this->cart = Cart::create(['user_id' => $this->user->id]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => 250000,
        ]);
    }

    public function test_user_can_initiate_bank_transfer_checkout_without_deducting_stock(): void
    {
        $response = $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
            'note' => 'Giao buổi sáng',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('pending', $session->status);
        $this->assertEquals(500000.00, (float) $session->amount);
        $this->assertStringStartsWith('HM', $session->transfer_content);
        $this->assertStringContainsString('vietqr.io', $session->qr_url);

        $response->assertRedirect(route('checkout.bank-payment', ['session_code' => $session->session_code]));

        // Kiểm tra tồn kho KHÔNG bị trừ khi mới tạo mã QR
        $this->variant->refresh();
        $this->assertEquals(10, $this->variant->stock_quantity);

        // Chưa có đơn hàng nào được tạo
        $this->assertEquals(0, Order::count());

        // Giỏ hàng vẫn còn nguyên
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $this->cart->id,
            'product_variant_id' => $this->variant->id,
        ]);
    }

    public function test_bank_payment_screen_displays_qr_and_bank_info(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user)->get(route('checkout.bank-payment', ['session_code' => $session->session_code]));

        $response->assertStatus(200);
        $response->assertSee($session->account_number);
        $response->assertSee($session->transfer_content);
        $response->assertSee('500.000');
    }

    public function test_qr_session_expires_after_10_minutes(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        // Đặt thời gian hết hạn về quá khứ
        $session->update(['expires_at' => now()->subMinutes(1)]);

        // Gọi API check status
        $response = $this->actingAs($this->user)->getJson(route('checkout.bank-payment.status', ['session_code' => $session->session_code]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'expired',
                'is_expired' => true,
                'remaining_seconds' => 0,
            ]);

        // Cố gắng completePayment trên phiên đã hết hạn phải ném Exception
        $this->expectException(\Exception::class);
        $session->completePayment();
    }

    public function test_complete_payment_deducts_stock_creates_order_and_clears_cart(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        // Thực hiện thanh toán thành công
        $order = $session->completePayment('FT987654321');

        $this->assertNotNull($order);
        $this->assertEquals('bank_transfer', $order->payment_method);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('pending', $order->order_status); // Chờ admin xác nhận để tránh sót đơn
        $this->assertEquals(500000.00, (float) $order->paid_amount);

        // Tồn kho PHẢI được trừ 2 sản phẩm (10 - 2 = 8)
        $this->variant->refresh();
        $this->assertEquals(8, $this->variant->stock_quantity);

        // Bản ghi sổ kho xuất hàng phải được tạo
        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $this->variant->id,
            'type' => 'sale',
            'quantity' => -2,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        // Bản ghi thanh toán phải được tạo với status paid
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'transaction_code' => 'FT987654321',
        ]);

        // Giỏ hàng của người dùng phải được làm trống
        $this->assertEquals(0, CartItem::where('cart_id', $this->cart->id)->count());

        // Phiên thanh toán chuyển trạng thái paid
        $session->refresh();
        $this->assertEquals('paid', $session->status);
        $this->assertEquals($order->id, $session->order_id);
    }

    public function test_simulate_endpoint_completes_order(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user)->post(route('checkout.bank-payment.simulate', ['session_code' => $session->session_code]));

        $session->refresh();
        $this->assertEquals('paid', $session->status);
        $this->assertNotNull($session->order);

        $response->assertRedirect(route('checkout.success', ['order_code' => $session->order->order_code]));
    }

    public function test_payment_webhook_matches_syntax_and_completes_order(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        // Gửi webhook từ ngân hàng với nội dung chứa mã HMxxxxxx và đủ thông tin xác thực SePay
        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => config('bank.account_number', '0988888888'),
            'content' => "Giao dich chuyen tien {$session->transfer_content} Nguyen Van Test",
            'transferAmount' => 500000,
            'referenceCode' => 'MB2026111001',
        ], [
            'Authorization' => 'Apikey '.config('bank.webhook_token'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $session->refresh();
        $this->assertEquals('paid', $session->status);
        $this->assertNotNull($session->order);

        // Tồn kho đã được trừ
        $this->variant->refresh();
        $this->assertEquals(8, $this->variant->stock_quantity);
    }

    public function test_sepay_test_ping_returns_http_200(): void
    {
        $response = $this->postJson(route('payment.webhook'), [
            'id' => 0,
            'gateway' => 'SePay',
            'code' => 'SEPAYTEST',
            'content' => 'SEPAY TEST WEBHOOK',
            'transferAmount' => 10000,
        ], [
            'Authorization' => 'Apikey '.config('bank.webhook_token'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_payment_webhook_rejects_insufficient_amount(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        // Số tiền thiếu (chỉ chuyển 300k trong khi đơn 500k)
        $response = $this->postJson(route('payment.webhook'), [
            'transferType' => 'in',
            'accountNumber' => config('bank.account_number', '0988888888'),
            'content' => "Chuyen tien {$session->transfer_content}",
            'transferAmount' => 300000,
        ], [
            'Authorization' => 'Apikey '.config('bank.webhook_token'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
            ]);

        $session->refresh();
        $this->assertEquals('pending', $session->status);
        $this->assertNull($session->order_id);

        // Tồn kho chưa bị trừ
        $this->variant->refresh();
        $this->assertEquals(10, $this->variant->stock_quantity);
    }

    public function test_user_can_cancel_bank_payment_session(): void
    {
        $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user)->post(route('checkout.bank-payment.cancel', ['session_code' => $session->session_code]));

        $session->refresh();
        $this->assertEquals('cancelled', $session->status);

        $response->assertRedirect(route('checkout.index'));

        // Tồn kho không đổi, chưa tạo đơn, giỏ hàng còn nguyên
        $this->variant->refresh();
        $this->assertEquals(10, $this->variant->stock_quantity);
        $this->assertEquals(0, Order::count());
        $this->assertEquals(1, CartItem::where('cart_id', $this->cart->id)->count());
    }

    public function test_cod_order_under_or_equal_400k_does_not_require_deposit(): void
    {
        // Cập nhật giỏ hàng 1 sản phẩm 250.000 đ (<= 400.000 đ)
        CartItem::where('cart_id', $this->cart->id)->update(['quantity' => 1]);

        $response = $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
            'note' => 'Giao buổi sáng',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertFalse((bool) $order->deposit_required);
        $this->assertEquals(0, (float) $order->paid_amount);
        $this->assertEquals(250000.00, (float) $order->remaining_amount);
        $this->assertEquals(250000.00, (float) $order->total_amount);

        // Tạo đơn ngay lập tức và redirect đến trang thành công
        $response->assertRedirect(route('checkout.success', ['order_code' => $order->order_code]));

        // Tồn kho bị trừ 1
        $this->variant->refresh();
        $this->assertEquals(9, $this->variant->stock_quantity);
    }

    public function test_cod_order_over_400k_requires_40_percent_deposit_via_vietqr(): void
    {
        // Giỏ hàng hiện tại là 2 x 250.000 đ = 500.000 đ (> 400.000 đ)
        $response = $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
            'note' => 'Cọc trước 40%',
        ]);

        $session = BankPaymentSession::where('user_id', $this->user->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('pending', $session->status);

        // Số tiền phiên chuyển khoản là đúng 40% của 500.000 đ = 200.000 đ
        $this->assertEquals(200000.00, (float) $session->amount);
        $this->assertTrue((bool) $session->order_data['is_deposit']);
        $this->assertTrue((bool) $session->order_data['deposit_required']);
        $this->assertEquals('cod', $session->order_data['payment_method']);
        $this->assertEquals(200000.00, (float) $session->order_data['deposit_amount']);
        $this->assertEquals(300000.00, (float) $session->order_data['remaining_amount']);
        $this->assertEquals(500000.00, (float) $session->order_data['total_amount']);

        $response->assertRedirect(route('checkout.bank-payment', ['session_code' => $session->session_code]));

        // Tồn kho chưa bị trừ khi chưa chuyển cọc
        $this->variant->refresh();
        $this->assertEquals(10, $this->variant->stock_quantity);

        // Giả lập hoàn tất cọc qua simulate
        $simResponse = $this->actingAs($this->user)->post(route('checkout.bank-payment.simulate', ['session_code' => $session->session_code]));

        $session->refresh();
        $this->assertEquals('paid', $session->status);
        $order = $session->order;
        $this->assertNotNull($order);

        // Đơn hàng COD nhưng có cọc:
        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('deposit_paid', $order->payment_status);
        $this->assertTrue((bool) $order->deposit_required);
        $this->assertEquals(200000.00, (float) $order->paid_amount);
        $this->assertEquals(300000.00, (float) $order->remaining_amount);
        $this->assertEquals(500000.00, (float) $order->total_amount);

        // Kho bị trừ sau khi cọc thành công
        $this->variant->refresh();
        $this->assertEquals(8, $this->variant->stock_quantity);

        // Payment bản ghi là deposit
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_type' => 'deposit',
            'amount' => 200000.00,
            'status' => 'paid',
        ]);

        $simResponse->assertRedirect(route('checkout.success', ['order_code' => $order->order_code]));
    }

    public function test_order_code_generation_follows_dh_short_sequential_format(): void
    {
        $code1 = Order::generateOrderCode();
        $this->assertEquals('DH0001', $code1);

        Order::create([
            'order_code' => $code1,
            'user_id' => $this->user->id,
            'receiver_name' => 'Test',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'subtotal' => 100000,
            'total_amount' => 100000,
        ]);

        $code2 = Order::generateOrderCode();
        $this->assertEquals('DH0002', $code2);
    }
}
