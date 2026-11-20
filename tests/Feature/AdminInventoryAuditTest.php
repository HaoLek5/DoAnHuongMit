<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Test',
        ]);

        $this->category = Category::create([
            'name' => 'Váy trẻ em',
            'description' => 'Thời trang cho bé',
        ]);
    }

    public function test_updating_variant_stock_automatically_creates_inventory_transaction(): void
    {
        $product = Product::create([
            'name' => 'Váy thỏ xanh',
            'category_id' => $this->category->id,
            'base_price' => 159000,
            'status' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price' => 159000,
            'stock_quantity' => 20,
        ]);

        $this->assertDatabaseCount('inventory_transactions', 0);

        // Admin updates stock from 20 to 25
        $response = $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'Váy thỏ xanh',
                'category_id' => $this->category->id,
                'base_price' => 159000,
                'status' => 1,
                'variants' => [
                    [
                        'id' => $variant->id,
                        'price' => 159000,
                        'stock_quantity' => 25,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.products.index'));

        // Variant stock updated
        $this->assertEquals(25, $variant->fresh()->stock_quantity);

        // Transaction recorded
        $this->assertDatabaseCount('inventory_transactions', 1);

        $tx = InventoryTransaction::first();
        $this->assertEquals($variant->id, $tx->variant_id);
        $this->assertEquals('adjustment', $tx->type);
        $this->assertEquals(5, $tx->quantity);
        $this->assertEquals('manual', $tx->reference_type);
        $this->assertEquals($this->admin->id, $tx->created_by);
        $this->assertStringContainsString('Kiểm kê cân bằng kho: 20 -> 25 (+5)', $tx->note);
        $this->assertEquals(20, $tx->system_stock);
        $this->assertEquals(25, $tx->actual_stock);
        $this->assertEquals(5, $tx->inc_quantity);
        $this->assertEquals(0, $tx->dec_quantity);
        $this->assertStringStartsWith('KK', $tx->code);

        // Check appearance on Inventory page
        $inventoryResponse = $this->actingAs($this->admin)
            ->get(route('admin.inventory.index'));

        $inventoryResponse->assertOk();
        $inventoryResponse->assertSee($tx->code);
        $inventoryResponse->assertSee('Váy thỏ xanh');
        $inventoryResponse->assertSee('+5');
    }

    public function test_updating_variant_stock_reduction_creates_negative_inventory_transaction(): void
    {
        $product = Product::create([
            'name' => 'Váy thỏ xanh',
            'category_id' => $this->category->id,
            'base_price' => 159000,
            'status' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price' => 159000,
            'stock_quantity' => 25,
        ]);

        // Admin decreases stock from 25 to 18 (-7)
        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'Váy thỏ xanh',
                'category_id' => $this->category->id,
                'base_price' => 159000,
                'variants' => [
                    [
                        'id' => $variant->id,
                        'price' => 159000,
                        'stock_quantity' => 18,
                    ],
                ],
            ]);

        $this->assertEquals(18, $variant->fresh()->stock_quantity);

        $tx = InventoryTransaction::latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(-7, $tx->quantity);
        $this->assertEquals(0, $tx->inc_quantity);
        $this->assertEquals(7, $tx->dec_quantity);
        $this->assertEquals(25, $tx->system_stock);
        $this->assertEquals(18, $tx->actual_stock);
    }

    public function test_updating_product_without_stock_change_does_not_create_duplicate_transactions(): void
    {
        $product = Product::create([
            'name' => 'Váy thỏ xanh',
            'category_id' => $this->category->id,
            'base_price' => 159000,
            'status' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price' => 159000,
            'stock_quantity' => 25,
        ]);

        // Update product name only, keep stock 25
        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'Váy thỏ xanh bản mới',
                'category_id' => $this->category->id,
                'base_price' => 160000,
                'variants' => [
                    [
                        'id' => $variant->id,
                        'price' => 160000,
                        'stock_quantity' => 25,
                    ],
                ],
            ]);

        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_creating_new_product_with_initial_stock_creates_initial_inventory_transaction(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'name' => 'Bình sữa Moyuum',
                'category_id' => $this->category->id,
                'base_price' => 365000,
                'default_stock' => 50,
            ]);

        $product = Product::where('name', 'Bình sữa Moyuum')->first();
        $this->assertNotNull($product);

        $tx = InventoryTransaction::where('variant_id', $product->variants->first()->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('adjustment', $tx->type);
        $this->assertEquals(50, $tx->quantity);
        $this->assertEquals(0, $tx->system_stock);
        $this->assertEquals(50, $tx->actual_stock);
        $this->assertStringContainsString('0 -> 50 (+50)', $tx->note);
    }
}
