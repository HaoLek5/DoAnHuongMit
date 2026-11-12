<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $catSua = Category::firstOrCreate(['name' => 'Sữa bột dinh dưỡng'], [
            'description' => 'Các dòng sữa bột chất lượng cao hỗ trợ phát triển toàn diện cho bé.',
        ]);
        $catBim = Category::firstOrCreate(['name' => 'Tã bỉm & Vệ sinh'], [
            'description' => 'Tã dán, tã quần siêu thấm hút mềm mại cho làn da bé.',
        ]);
        $catBinh = Category::firstOrCreate(['name' => 'Bình sữa & Đồ dùng ăn uống'], [
            'description' => 'Bình sữa silicon, máy tiệt trùng, núm ti chống sặc.',
        ]);
        $catAnDam = Category::firstOrCreate(['name' => 'Thực phẩm ăn dặm'], [
            'description' => 'Bột ăn dặm, bánh ăn dặm, sinh tố hoa quả hữu cơ.',
        ]);

        // 2. Attributes & Values
        $attrTrongLuong = Attribute::firstOrCreate(['code' => 'trong_luong'], ['name' => 'Trọng lượng']);
        $val400g = AttributeValue::firstOrCreate(['attribute_id' => $attrTrongLuong->id, 'value' => '400g'], ['sort_order' => 1]);
        $val800g = AttributeValue::firstOrCreate(['attribute_id' => $attrTrongLuong->id, 'value' => '800g'], ['sort_order' => 2]);
        $val900g = AttributeValue::firstOrCreate(['attribute_id' => $attrTrongLuong->id, 'value' => '900g'], ['sort_order' => 3]);

        $attrHuongVi = Attribute::firstOrCreate(['code' => 'huong_vi'], ['name' => 'Hương vị']);
        $valVani = AttributeValue::firstOrCreate(['attribute_id' => $attrHuongVi->id, 'value' => 'Vani tự nhiên'], ['sort_order' => 1]);
        $valDau = AttributeValue::firstOrCreate(['attribute_id' => $attrHuongVi->id, 'value' => 'Dâu tây'], ['sort_order' => 2]);

        $attrSize = Attribute::firstOrCreate(['code' => 'size_ta'], ['name' => 'Kích cỡ tã']);
        $valSizeM = AttributeValue::firstOrCreate(['attribute_id' => $attrSize->id, 'value' => 'Size M (6 - 11kg)'], ['sort_order' => 1]);
        $valSizeL = AttributeValue::firstOrCreate(['attribute_id' => $attrSize->id, 'value' => 'Size L (9 - 14kg)'], ['sort_order' => 2]);
        $valSizeXL = AttributeValue::firstOrCreate(['attribute_id' => $attrSize->id, 'value' => 'Size XL (12 - 17kg)'], ['sort_order' => 3]);

        // 3. Products
        // Product 1: Sữa bột Friso Gold Pro số 3
        $p1 = Product::create([
            'category_id' => $catSua->id,
            'name' => 'Sữa bột Friso Gold Pro số 3 800g (1 - 3 tuổi)',
            'description' => 'Sữa bột công thức nhập khẩu 100% từ Hà Lan, bổ sung dưỡng chất BioPro+ giúp đường ruột bé khỏe mạnh và tăng cường miễn dịch.',
            'base_price' => 545000,
            'status' => true,
        ]);

        ProductImage::create([
            'product_id' => $p1->id,
            'image_url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=80',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        // Variants for P1 (Trọng lượng: 400g vs 800g)
        $var1 = ProductVariant::create([
            'product_id' => $p1->id,
            'price' => 285000,
            'cost_price' => 230000,
            'stock_quantity' => 45,
        ]);
        $var1->attributeValues()->attach($val400g->id);

        $var2 = ProductVariant::create([
            'product_id' => $p1->id,
            'price' => 545000,
            'cost_price' => 440000,
            'stock_quantity' => 120,
        ]);
        $var2->attributeValues()->attach($val800g->id);

        // Product 2: Tã quần Bobby Lõi Nén Thần Kỳ
        $p2 = Product::create([
            'category_id' => $catBim->id,
            'name' => 'Tã quần Bobby Lõi Nén Khí Khô Thoáng Gấp 3 Lần',
            'description' => 'Công nghệ đệm lưng 3D cùng bề mặt hàng ngàn lỗ thấm hút, cho làn da bé luôn khô thoáng mềm mại suốt đêm dài.',
            'base_price' => 295000,
            'status' => true,
        ]);

        ProductImage::create([
            'product_id' => $p2->id,
            'image_url' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=600&auto=format&fit=crop&q=80',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $varBimM = ProductVariant::create([
            'product_id' => $p2->id,
            'price' => 295000,
            'cost_price' => 220000,
            'stock_quantity' => 60,
        ]);
        $varBimM->attributeValues()->attach($valSizeM->id);

        $varBimL = ProductVariant::create([
            'product_id' => $p2->id,
            'price' => 310000,
            'cost_price' => 235000,
            'stock_quantity' => 85,
        ]);
        $varBimL->attributeValues()->attach($valSizeL->id);

        $varBimXL = ProductVariant::create([
            'product_id' => $p2->id,
            'price' => 325000,
            'cost_price' => 250000,
            'stock_quantity' => 40,
        ]);
        $varBimXL->attributeValues()->attach($valSizeXL->id);

        // Product 3: Bình sữa Moyuum Silicon Cao Cấp
        $p3 = Product::create([
            'category_id' => $catBinh->id,
            'name' => 'Bình sữa Moyuum All-in-One PPSU 270ml',
            'description' => 'Bình sữa chống sặc xuất xứ Hàn Quốc, chịu nhiệt tới 200 độ C, núm ti mô phỏng bầu ngực mẹ tự nhiên.',
            'base_price' => 365000,
            'status' => true,
        ]);

        ProductImage::create([
            'product_id' => $p3->id,
            'image_url' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=600&auto=format&fit=crop&q=80',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $varBinh = ProductVariant::create([
            'product_id' => $p3->id,
            'price' => 365000,
            'cost_price' => 270000,
            'stock_quantity' => 50,
        ]);
        // Default single variant without attribute values
    }
}
