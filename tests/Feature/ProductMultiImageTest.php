<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMultiImageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->category = Category::create([
            'name' => 'Sữa bột dinh dưỡng',
            'description' => 'Các dòng sữa',
        ]);
    }

    public function test_can_create_product_with_up_to_5_images(): void
    {
        $images = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg'),
            UploadedFile::fake()->image('img3.jpg'),
            UploadedFile::fake()->image('img4.jpg'),
            UploadedFile::fake()->image('img5.jpg'),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/products', [
                'name' => 'Sữa Enfa A+ 800g',
                'category_id' => $this->category->id,
                'base_price' => 450000,
                'description' => 'Sữa giàu dinh dưỡng dành cho trẻ nhỏ.',
                'images' => $images,
                'primary_new_image_index' => 2,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $product = Product::with('images')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Sữa giàu dinh dưỡng dành cho trẻ nhỏ.', $product->description);
        $this->assertCount(5, $product->images);

        // Check primary image index 2
        $primary = $product->images()->where('is_primary', true)->first();
        $this->assertNotNull($primary);
    }

    public function test_cannot_create_product_with_more_than_5_images(): void
    {
        $images = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg'),
            UploadedFile::fake()->image('img3.jpg'),
            UploadedFile::fake()->image('img4.jpg'),
            UploadedFile::fake()->image('img5.jpg'),
            UploadedFile::fake()->image('img6.jpg'),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/products', [
                'name' => 'Sữa Quá Nhiều Ảnh',
                'category_id' => $this->category->id,
                'base_price' => 500000,
                'description' => 'Thử nghiệm quá 5 ảnh',
                'images' => $images,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['images']);
    }

    public function test_product_json_returns_description_and_all_images_for_storefront(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Sữa Nan Nga',
            'description' => 'Mô tả chi tiết sản phẩm Sữa Nan',
            'base_price' => 380000,
            'status' => true,
        ]);

        $product->images()->create([
            'image_url' => '/storage/products/nan1.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $product->images()->create([
            'image_url' => '/storage/products/nan2.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson("/san-pham/{$product->id}/json");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => 'Sữa Nan Nga',
                'description' => 'Mô tả chi tiết sản phẩm Sữa Nan',
            ],
        ]);

        $this->assertCount(2, $response->json('product.images'));
    }
}
