<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatisticTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_stat_test@haomilk.vn',
            'role' => 'admin',
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Customer Demo',
            'email' => 'customer_stat_test@haomilk.vn',
            'role' => 'customer',
        ]);
    }

    /**
     * Guest cannot access admin statistics.
     */
    public function test_guest_cannot_access_statistics(): void
    {
        $response = $this->get('/admin/statistics');
        $response->assertRedirect('/dang-nhap');
    }

    /**
     * Customer cannot access admin statistics.
     */
    public function test_customer_cannot_access_statistics(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/statistics');
        $response->assertRedirect(route('home'));
    }

    /**
     * Admin can view statistics dashboard and see metrics.
     */
    public function test_admin_can_view_statistics_page(): void
    {
        $category = Category::create([
            'name' => 'Sữa bột dinh dưỡng',
            'slug' => 'sua-bot-dinh-duong',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sữa Meiji Số 9',
            'base_price' => 500000,
            'status' => true,
            'description' => 'Sữa Meiji Nhật',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price' => 500000,
            'cost_price' => 400000,
            'stock_quantity' => 15,
        ]);

        $order = Order::create([
            'order_code' => 'DH-TEST-STAT-01',
            'user_id' => $this->customer->id,
            'receiver_name' => 'Customer Demo',
            'receiver_phone' => '0988776655',
            'shipping_address' => 'Hà Nội',
            'subtotal' => 500000,
            'shipping_fee' => 0,
            'total_amount' => 500000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'order_status' => 'confirmed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'variant_id' => $variant->id,
            'product_name' => 'Sữa Meiji Số 9',
            'sku' => 'MEIJI-09',
            'variant_name' => 'Mặc định',
            'price' => 500000,
            'quantity' => 2,
            'subtotal' => 1000000,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/statistics');

        $response->assertStatus(200)
            ->assertSee('Doanh thu thực thu')
            ->assertSee('500,000')
            ->assertSee('Sữa Meiji Số 9');
    }

    /**
     * Admin can filter statistics by custom period.
     */
    public function test_admin_can_filter_statistics_by_period(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/statistics?period=today');
        $response->assertStatus(200)->assertSee('Hôm nay');

        $response30 = $this->actingAs($this->admin)->get('/admin/statistics?period=30days');
        $response30->assertStatus(200)->assertSee('30 ngày qua');

        $responseYear = $this->actingAs($this->admin)->get('/admin/statistics?period=this_year');
        $responseYear->assertStatus(200)->assertSee('Năm');

        $responseCustom = $this->actingAs($this->admin)->get('/admin/statistics?period=custom&from_date='.date('Y-m-d').'&to_date='.date('Y-m-d'));
        $responseCustom->assertStatus(200);
    }

    /**
     * Customer list view does not show the 4 removed cards, and sidebar does not have settings or overview.
     */
    public function test_customer_list_view_and_sidebar_modifications(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/customers');

        $response->assertStatus(200);
        // Sidebar has "Thống kê"
        $response->assertSee('Thống kê');
        // Sidebar does NOT have "Tổng quan"
        $response->assertDontSee('Tổng quan');
        // Sidebar does NOT have "Cài đặt hệ thống"
        $response->assertDontSee('Cài đặt hệ thống');
    }

    /**
     * Accessing admin root /admin directs straight to statistics.
     */
    public function test_admin_root_renders_statistics(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertStatus(200)
            ->assertSee('Thống kê')
            ->assertSee('Doanh thu thực thu');
    }
}
