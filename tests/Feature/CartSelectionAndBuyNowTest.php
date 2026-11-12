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
use Tests\TestCase;

class CartSelectionAndBuyNowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected Product $product1;

    protected ProductVariant $variant1;

    protected Product $product2;

    protected ProductVariant $variant2;

    protected Address $address;

    protected Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'name' => 'Nguyễn Mua Hàng',
            'phone' => '0988776655',
        ]);

        $this->address = Address::create([
            'user_id' => $this->user->id,
            'receiver_name' => 'Nguyễn Mua Hàng',
            'receiver_phone' => '0988776655',
            'province' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'ward' => 'Dịch Vọng',
            'detail_address' => 'Số 12 Duy Tân',
            'is_default' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Sữa và Đồ Dùng',
        ]);

        $this->product1 = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sản phẩm 1',
            'base_price' => 100000,
            'status' => true,
        ]);
        $this->variant1 = ProductVariant::create([
            'product_id' => $this->product1->id,
            'price' => 100000,
            'stock_quantity' => 10,
        ]);

        $this->product2 = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sản phẩm 2',
            'base_price' => 200000,
            'status' => true,
        ]);
        $this->variant2 = ProductVariant::create([
            'product_id' => $this->product2->id,
            'price' => 200000,
            'stock_quantity' => 5,
        ]);

        $this->cart = Cart::create(['user_id' => $this->user->id]);
    }

    public function test_buy_now_endpoint_returns_cart_item_id_and_sets_quantity(): void
    {
        $response = $this->actingAs($this->user)->postJson('/gio-hang/them', [
            'product_id' => $this->product1->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 2,
            'buy_now' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'cart_item_id',
            ]);

        $cartItemId = $response->json('cart_item_id');
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItemId,
            'cart_id' => $this->cart->id,
            'quantity' => 2,
        ]);
    }

    public function test_checkout_index_with_selected_items_only_loads_chosen_items(): void
    {
        $item1 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product1->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        $item2 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product2->id,
            'product_variant_id' => $this->variant2->id,
            'quantity' => 2,
            'price' => 200000,
        ]);

        // Access checkout with only item1
        $response = $this->actingAs($this->user)->get(route('checkout.index', ['items' => $item1->id]));

        $response->assertOk();
        $response->assertSee('Sản phẩm 1');
        $response->assertDontSee('Sản phẩm 2');
    }

    public function test_cod_checkout_only_removes_selected_items_leaving_others_in_cart(): void
    {
        $item1 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product1->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        $item2 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product2->id,
            'product_variant_id' => $this->variant2->id,
            'quantity' => 2,
            'price' => 200000,
        ]);

        // Place COD order for item1 only
        $response = $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
            'items' => (string) $item1->id,
        ]);

        $response->assertRedirect();

        // Item 1 must be deleted from cart
        $this->assertDatabaseMissing('cart_items', ['id' => $item1->id]);

        // Item 2 must still remain in cart!
        $this->assertDatabaseHas('cart_items', [
            'id' => $item2->id,
            'quantity' => 2,
        ]);

        // Order items should only contain item1
        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertCount(1, $order->items);
        $this->assertEquals('Sản phẩm 1', $order->items->first()->product_name);
    }

    public function test_bank_transfer_checkout_only_removes_selected_items_upon_payment_completion(): void
    {
        $item1 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product1->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        $item2 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product2->id,
            'product_variant_id' => $this->variant2->id,
            'quantity' => 2,
            'price' => 200000,
        ]);

        // Initiate bank transfer checkout with item1 only
        $response = $this->actingAs($this->user)->post(route('checkout.process'), [
            'address_type' => 'existing',
            'address_id' => $this->address->id,
            'payment_method' => 'bank_transfer',
            'items' => (string) $item1->id,
        ]);

        $response->assertRedirect();

        /** @var BankPaymentSession $session */
        $session = BankPaymentSession::where('user_id', $this->user->id)->latest()->first();
        $this->assertNotNull($session);
        $this->assertEquals(100000, $session->amount);

        // Simulate payment completion
        $session->completePayment('TEST-REF-123');

        // Item 1 must be removed, item 2 must stay
        $this->assertDatabaseMissing('cart_items', ['id' => $item1->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $item2->id]);
    }
}
