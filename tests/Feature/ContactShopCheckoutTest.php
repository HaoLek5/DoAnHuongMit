<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected Category $category;

    protected Product $product;

    protected ProductVariant $variant;

    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->category = Category::create([
            'name' => 'Thời trang cho bé',
            'description' => 'Váy bé gái',
        ]);

        $this->product = Product::create([
            'name' => 'Váy hoa nhí Hương Mít',
            'category_id' => $this->category->id,
            'base_price' => 500000, // > 400k để kiểm tra không bị ép cọc
            'status' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 500000,
            'stock_quantity' => 10,
        ]);

        $this->address = Address::create([
            'user_id' => $this->user->id,
            'receiver_name' => 'Nguyễn Thị Hoa',
            'receiver_phone' => '0987654321',
            'province' => 'Hà Nội',
            'district' => 'Nam Từ Liêm',
            'ward' => 'Mễ Trì',
            'detail_address' => 'Số 28 ngách 24',
            'is_default' => true,
        ]);
    }

    public function test_homepage_does_not_contain_footer_and_quality_commitment_badge(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // Không có phần footer trên trang chủ
        $response->assertDontSee('© '.date('Y').' Hương Mít. Tất cả quyền được bảo lưu.');
        $response->assertDontSee('Sản phẩm chất lượng cao, an toàn tuyệt đối và chăm sóc tận tâm.');
        // Không có badge cam kết chất lượng
        $response->assertDontSee('Sản phẩm chất lượng cho Mẹ & Bé');
    }

    public function test_checkout_page_shows_contact_shop_option_and_locks_cod_and_bank(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'price' => 500000,
        ]);

        $response = $this->actingAs($this->user)->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('Liên hệ shop');
        $response->assertSee('Tạm khóa');
    }

    public function test_customer_can_place_order_with_contact_shop_without_deposit(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => 500000, // Total = 1,000,000 > 400k
        ]);

        $response = $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'contact_shop',
            'note' => 'Giao buổi chiều giúp em',
        ]);

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);

        $response->assertRedirect(route('checkout.success', ['order_code' => $order->order_code]));

        $this->assertEquals('contact_shop', $order->payment_method);
        $this->assertEquals('Liên hệ shop', $order->payment_method_label);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertEquals('pending', $order->order_status);
        $this->assertFalse($order->deposit_required);
        $this->assertEquals(0, (float) $order->deposit_amount);
        $this->assertEquals(1000000, (float) $order->total_amount);
        $this->assertEquals(1000000, (float) $order->remaining_amount);

        // Trừ tồn kho và ghi sổ kiểm kho
        $this->assertEquals(8, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $this->variant->id,
            'type' => 'sale',
            'quantity' => -2,
        ]);

        // Giỏ hàng được làm trống
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);

        // Kiểm tra hiển thị trong Admin
        $adminIndex = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $adminIndex->assertOk();
        $adminIndex->assertSee('Liên hệ shop');

        $adminShow = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $adminShow->assertOk();
        $adminShow->assertSee('Liên hệ shop');
    }
}
