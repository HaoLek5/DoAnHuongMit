<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_test@haomilk.vn',
            'role' => 'admin',
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Customer Demo',
            'email' => 'customer_test@haomilk.vn',
            'phone' => '0988776655',
            'role' => 'customer',
        ]);
    }

    /**
     * Guests are redirected to login.
     */
    public function test_guest_cannot_access_admin_customers(): void
    {
        $response = $this->get('/admin/customers');
        $response->assertRedirect('/dang-nhap');
    }

    /**
     * Regular customers cannot access admin customer management.
     */
    public function test_customer_cannot_access_admin_customers(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/customers');
        $response->assertRedirect(route('home'));
    }

    /**
     * Admin can view customer list.
     */
    public function test_admin_can_view_customers_list(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/customers');

        $response->assertStatus(200)
            ->assertSee('Tài khoản khách hàng')
            ->assertSee('Customer Demo')
            ->assertSee('customer_test@haomilk.vn');
    }

    /**
     * Admin can view customer detail page with address and orders.
     */
    public function test_admin_can_view_customer_detail(): void
    {
        Address::create([
            'user_id' => $this->customer->id,
            'receiver_name' => 'Customer Demo',
            'receiver_phone' => '0988776655',
            'province' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'ward' => 'Dịch Vọng',
            'detail_address' => 'Số 10 Phạm Văn Đồng',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/customers/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertSee('Chi tiết tài khoản khách hàng')
            ->assertSee('Customer Demo')
            ->assertSee('Số 10 Phạm Văn Đồng');
    }

    /**
     * Admin can lock and unlock customer account.
     */
    public function test_admin_can_toggle_lock_customer(): void
    {
        // 1. Lock customer
        $response = $this->actingAs($this->admin)
            ->post("/admin/customers/{$this->customer->id}/toggle-lock");

        $response->assertRedirect();
        $this->customer->refresh();
        $this->assertTrue($this->customer->isLocked());

        // 2. Unlock customer
        $response = $this->actingAs($this->admin)
            ->post("/admin/customers/{$this->customer->id}/toggle-lock");

        $response->assertRedirect();
        $this->customer->refresh();
        $this->assertFalse($this->customer->isLocked());
    }

    /**
     * Admin can update customer profile information.
     */
    public function test_admin_can_update_customer_info(): void
    {
        $response = $this->actingAs($this->admin)
            ->put("/admin/customers/{$this->customer->id}", [
                'name' => 'Customer Updated Name',
                'email' => 'customer_updated@haomilk.vn',
                'phone' => '0911223344',
            ]);

        $response->assertRedirect();
        $this->customer->refresh();
        $this->assertEquals('Customer Updated Name', $this->customer->name);
        $this->assertEquals('customer_updated@haomilk.vn', $this->customer->email);
        $this->assertEquals('0911223344', $this->customer->phone);
    }

    /**
     * Admin can reset customer password.
     */
    public function test_admin_can_reset_customer_password(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/customers/{$this->customer->id}/reset-password", [
                'password' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $this->customer->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->customer->password));
    }

    /**
     * Admin cannot lock own account.
     */
    public function test_admin_cannot_lock_own_account(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/customers/{$this->admin->id}/toggle-lock");

        $response->assertRedirect();
        $this->admin->refresh();
        $this->assertFalse($this->admin->isLocked());
    }
}
