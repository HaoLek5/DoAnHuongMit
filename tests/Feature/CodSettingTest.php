<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->category = Category::create([
            'name' => 'Sữa bột dinh dưỡng',
            'description' => 'Các dòng sữa',
        ]);
    }

    public function test_admin_can_toggle_product_cod(): void
    {
        $product = Product::create([
            'name' => 'Sữa Friso Gold',
            'category_id' => $this->category->id,
            'base_price' => 500000,
            'status' => true,
            'allow_cod' => true,
        ]);

        $this->assertTrue($product->allow_cod);

        // Toggle COD tắt
        $response = $this->actingAs($this->admin)
            ->postJson("/admin/products/{$product->id}/toggle-cod");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'allow_cod' => false,
            ]);

        $product->refresh();
        $this->assertFalse($product->allow_cod);

        // Toggle COD bật lại
        $response2 = $this->actingAs($this->admin)
            ->postJson("/admin/products/{$product->id}/toggle-cod");

        $response2->assertOk()
            ->assertJson([
                'success' => true,
                'allow_cod' => true,
            ]);

        $product->refresh();
        $this->assertTrue($product->allow_cod);
    }

    public function test_checkout_disallows_cod_when_product_has_cod_disabled(): void
    {
        $product = Product::create([
            'name' => 'Sản phẩm chỉ chuyển khoản',
            'category_id' => $this->category->id,
            'base_price' => 200000,
            'status' => true,
            'allow_cod' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price' => 200000,
            'stock_quantity' => 10,
        ]);

        $cart = Cart::create([
            'user_id' => $this->customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 200000,
        ]);

        $address = Address::create([
            'user_id' => $this->customer->id,
            'receiver_name' => 'Nguyễn Văn A',
            'receiver_phone' => '0987654321',
            'province' => 'Hà Nội',
            'district' => 'Thanh Xuân',
            'ward' => 'Triều Khúc',
            'detail_address' => 'Số 10 Phố Triều Khúc',
            'is_default' => true,
        ]);

        // Cố gắng đặt hàng bằng COD
        $response = $this->actingAs($this->customer)
            ->post(route('checkout.process'), [
                'address_type' => 'existing',
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_checkout_allows_cod_when_product_has_cod_enabled(): void
    {
        $product = Product::create([
            'name' => 'Sản phẩm hỗ trợ COD',
            'category_id' => $this->category->id,
            'base_price' => 200000,
            'status' => true,
            'allow_cod' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price' => 200000,
            'stock_quantity' => 10,
        ]);

        $cart = Cart::create([
            'user_id' => $this->customer->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 200000,
        ]);

        $address = Address::create([
            'user_id' => $this->customer->id,
            'receiver_name' => 'Nguyễn Văn A',
            'receiver_phone' => '0987654321',
            'province' => 'Hà Nội',
            'district' => 'Thanh Xuân',
            'ward' => 'Triều Khúc',
            'detail_address' => 'Số 10 Phố Triều Khúc',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->customer)
            ->post(route('checkout.process'), [
                'address_type' => 'existing',
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->customer->id,
            'payment_method' => 'cod',
        ]);
    }
}
