<?php

namespace Tests\Feature;

use App\Models\BankPaymentSession;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleCampaign;
use App\Models\SaleCampaignItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSaleCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected Category $category;

    protected Product $product;

    protected ProductVariant $variant;

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
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sữa Dinh Dưỡng Meiji 800g',
            'base_price' => 500000,
            'status' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'price' => 500000,
            'stock_quantity' => 50,
        ]);
    }

    public function test_admin_can_view_sale_campaigns_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sale-campaigns.index'));

        $response->assertStatus(200);
        $response->assertSee('Chiến dịch Sale');
    }

    public function test_admin_can_create_sale_campaign(): void
    {
        $startTime = now()->subHour()->format('Y-m-d\TH:i');
        $endTime = now()->addDays(2)->format('Y-m-d\TH:i');

        $payload = [
            'name' => 'Flash Sale Cuối Tuần 11.11',
            'description' => 'Chương trình sale giảm giá mạnh sữa Meiji',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_variant_id' => $this->variant->id,
                    'sale_price' => 380000,
                    'sale_quantity' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.sale-campaigns.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $campaign = SaleCampaign::with('items')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('Flash Sale Cuối Tuần 11.11', $campaign->name);
        $this->assertEquals('running', $campaign->status);
        $this->assertCount(1, $campaign->items);

        $item = $campaign->items->first();
        $this->assertEquals(380000, $item->sale_price);
        $this->assertEquals(20, $item->sale_quantity);
        $this->assertEquals(0, $item->sold_quantity);
    }

    public function test_cannot_create_campaign_with_invalid_times_or_empty_items(): void
    {
        // End time before start time
        $payload = [
            'name' => 'Sale Lỗi Thời Gian',
            'start_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'end_time' => now()->format('Y-m-d\TH:i'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_variant_id' => $this->variant->id,
                    'sale_price' => 300000,
                    'sale_quantity' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.sale-campaigns.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
    }

    public function test_customer_gets_sale_price_in_cart_when_campaign_is_active(): void
    {
        // Create active sale campaign
        $campaign = SaleCampaign::create([
            'name' => 'Flash Sale Trưa Vàng',
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addHours(2),
            'is_active' => true,
        ]);

        SaleCampaignItem::create([
            'sale_campaign_id' => $campaign->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'original_price' => 500000,
            'sale_price' => 350000,
            'sale_quantity' => 15,
            'sold_quantity' => 0,
        ]);

        // Customer adds variant to cart
        $response = $this->actingAs($this->customer)
            ->postJson(route('cart.add'), [
                'product_id' => $this->product->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify cart item has sale price (350.000)
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => 350000,
        ]);
    }

    public function test_checkout_increments_sold_quantity_on_sale_item(): void
    {
        // Active campaign with 10 slots
        $campaign = SaleCampaign::create([
            'name' => 'Sale Khai Trương',
            'start_time' => now()->subHour(),
            'end_time' => now()->addDays(1),
            'is_active' => true,
        ]);

        $saleItem = SaleCampaignItem::create([
            'sale_campaign_id' => $campaign->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'original_price' => 500000,
            'sale_price' => 350000,
            'sale_quantity' => 10,
            'sold_quantity' => 0,
        ]);

        // Add 3 items to cart
        $this->actingAs($this->customer)
            ->postJson(route('cart.add'), [
                'product_id' => $this->product->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 3,
            ]);

        // Process checkout
        $response = $this->actingAs($this->customer)
            ->post(route('checkout.process'), [
                'address_type' => 'new',
                'receiver_name' => 'Nguyen Van A',
                'receiver_phone' => '0901234567',
                'province' => 'Hà Nội',
                'district' => 'Cầu Giấy',
                'ward' => 'Dịch Vọng',
                'detail_address' => 'Số 123 Đường Cầu Giấy',
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(302);

        // Đơn 3 x 350.000 đ = 1.050.000 đ (> 400.000 đ) yêu cầu cọc 40% qua VietQR, hoàn tất cọc để kích hoạt đơn
        $session = BankPaymentSession::where('user_id', $this->customer->id)->first();
        if ($session) {
            $session->completePayment();
        }

        // Verify sold_quantity on sale item was incremented to 3
        $saleItem->refresh();
        $this->assertEquals(3, $saleItem->sold_quantity);
        $this->assertEquals(7, $saleItem->remaining_quantity);
    }

    public function test_admin_can_toggle_campaign_status(): void
    {
        $campaign = SaleCampaign::create([
            'name' => 'Chiến dịch Test Toggle',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.sale-campaigns.toggle-status', $campaign));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_active' => false,
            'status' => 'paused',
        ]);

        $campaign->refresh();
        $this->assertFalse($campaign->is_active);
    }
}
