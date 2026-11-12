<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products with search and filters
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'primaryImage', 'variants.attributeValues.attribute'])
            ->latest('id');

        // Search by name or product code (SP0001...)
        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");

                if (preg_match('/^SP(\d+)$/i', $search, $matches)) {
                    $q->orWhere('id', (int) $matches[1]);
                } elseif (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by status
        if ($request->has('status') && $request->input('status') !== '' && $request->input('status') !== null) {
            $query->where('status', (bool) $request->input('status'));
        }

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();
        $nextCode = Product::getNextCode();

        return view('admin.products.index', compact(
            'products',
            'categories',
            'attributes',
            'nextCode'
        ));
    }

    /**
     * Get product details for Edit modal via AJAX
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'images', 'variants.attributeValues.attribute']);

        return response()->json([
            'success' => true,
            'product' => $product,
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable'],
            'image' => ['nullable', 'file', 'image', 'max:5120'], // Max 5MB (single fallback)
            'images' => ['nullable', 'array', 'max:5'], // Max 5 images per product
            'images.*' => ['file', 'image', 'max:5120'],
            'primary_new_image_index' => ['nullable', 'integer'],
            'variants' => ['nullable', 'array'],
        ], [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_id.required' => 'Vui lòng chọn danh mục cho sản phẩm.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'base_price.required' => 'Vui lòng nhập giá gốc sản phẩm.',
            'base_price.numeric' => 'Giá sản phẩm phải là chữ số.',
            'base_price.min' => 'Giá sản phẩm không được nhỏ hơn 0.',
            'images.max' => 'Mỗi sản phẩm chỉ được có tối đa 5 ảnh.',
            'images.*.image' => 'File tải lên phải là hình ảnh (jpg, png, webp...).',
            'images.*.max' => 'Dung lượng mỗi ảnh phải dưới 5MB.',
            'image.image' => 'File tải lên phải là hình ảnh (jpg, png, webp...).',
            'image.max' => 'Dung lượng ảnh phải dưới 5MB.',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'base_price' => $validated['base_price'],
                'status' => $request->has('status') ? (bool) $request->input('status') : true,
            ]);

            // Handle Image Uploads (Tối đa 5 ảnh)
            $uploadedFiles = [];
            if ($request->hasFile('images')) {
                $uploadedFiles = array_slice($request->file('images'), 0, 5);
            } elseif ($request->hasFile('image')) {
                $uploadedFiles = [$request->file('image')];
            }

            $primaryNewIndex = (int) $request->input('primary_new_image_index', 0);

            foreach ($uploadedFiles as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = 'prod_'.$product->id.'_'.time().'_'.$index.'.'.$file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => Storage::url($path),
                        'is_primary' => ($index === $primaryNewIndex),
                        'sort_order' => $index + 1,
                    ]);
                }
            }

            // Ensure at least one image is primary if images exist
            if ($product->images()->exists() && $product->images()->where('is_primary', true)->doesntExist()) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }

            // Handle Variants
            $variantsData = $request->input('variants', []);
            if (! empty($variantsData) && is_array($variantsData)) {
                foreach ($variantsData as $vData) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'price' => ! empty($vData['price']) ? (float) $vData['price'] : $product->base_price,
                        'cost_price' => ! empty($vData['cost_price']) ? (float) $vData['cost_price'] : null,
                        'stock_quantity' => ! empty($vData['stock_quantity']) ? (int) $vData['stock_quantity'] : 0,
                    ]);

                    $valueIds = [];

                    // Save attribute values on submit
                    if (! empty($vData['attribute_values']) && is_array($vData['attribute_values'])) {
                        foreach ($vData['attribute_values'] as $av) {
                            if (! empty($av['attribute_id']) && ! empty($av['value'])) {
                                $attrVal = AttributeValue::firstOrCreate(
                                    [
                                        'attribute_id' => $av['attribute_id'],
                                        'value' => trim($av['value']),
                                    ],
                                    [
                                        'sort_order' => (AttributeValue::where('attribute_id', $av['attribute_id'])->max('sort_order') ?? 0) + 1,
                                        'status' => true,
                                    ]
                                );
                                $valueIds[] = $attrVal->id;
                            }
                        }
                    } elseif (! empty($vData['attribute_value_ids']) && is_array($vData['attribute_value_ids'])) {
                        $valueIds = $vData['attribute_value_ids'];
                    }

                    if (! empty($valueIds)) {
                        $variant->attributeValues()->sync($valueIds);
                    }
                }
            } else {
                // Default single variant with base price if no attributes specified
                ProductVariant::create([
                    'product_id' => $product->id,
                    'price' => $product->base_price,
                    'cost_price' => null,
                    'stock_quantity' => (int) $request->input('default_stock', 0),
                ]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thêm sản phẩm ['.$product->code.'] thành công!',
                    'product' => $product->load(['category', 'images', 'variants.attributeValues']),
                    'next_code' => Product::getNextCode(),
                ]);
            }

            return redirect()->route('admin.products.index')->with('success', 'Thêm sản phẩm ['.$product->code.'] thành công!');
        } catch (\Throwable $th) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi lưu sản phẩm: '.$th->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Lỗi lưu sản phẩm: '.$th->getMessage());
        }
    }

    /**
     * Update an existing product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable'],
            'image' => ['nullable', 'file', 'image', 'max:5120'], // Max 5MB (single fallback)
            'images' => ['nullable', 'array', 'max:5'], // Max 5 images
            'images.*' => ['file', 'image', 'max:5120'],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => ['integer'],
            'primary_image_id' => ['nullable'],
            'primary_new_image_index' => ['nullable'],
            'variants' => ['nullable', 'array'],
        ], [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'category_id.required' => 'Vui lòng chọn danh mục cho sản phẩm.',
            'base_price.required' => 'Vui lòng nhập giá gốc sản phẩm.',
            'images.max' => 'Mỗi sản phẩm chỉ được có tối đa 5 ảnh.',
            'images.*.image' => 'File tải lên phải là hình ảnh (jpg, png, webp...).',
            'images.*.max' => 'Dung lượng mỗi ảnh phải dưới 5MB.',
            'image.image' => 'File tải lên phải là hình ảnh (jpg, png, webp...).',
            'image.max' => 'Dung lượng ảnh phải dưới 5MB.',
        ]);

        $deletedImageIds = $request->input('deleted_image_ids', []);
        if (! is_array($deletedImageIds)) {
            $deletedImageIds = [];
        }

        // Collect newly uploaded files
        $uploadedFiles = [];
        if ($request->hasFile('images')) {
            $uploadedFiles = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $uploadedFiles = [$request->file('image')];
        }

        // Validate that total images after delete + add do not exceed 5
        $currentRemainingCount = $product->images()->whereNotIn('id', $deletedImageIds)->count();
        if (($currentRemainingCount + count($uploadedFiles)) > 5) {
            $msg = 'Mỗi sản phẩm chỉ được có tối đa 5 ảnh. Hiện tại sản phẩm sẽ có '.($currentRemainingCount + count($uploadedFiles)).' ảnh.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withInput()->with('error', $msg);
        }

        try {
            DB::beginTransaction();

            $product->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'base_price' => $validated['base_price'],
                'status' => $request->has('status') ? (bool) $request->input('status') : true,
            ]);

            // 1. Delete removed images from storage and DB
            if (! empty($deletedImageIds)) {
                $imagesToDelete = $product->images()->whereIn('id', $deletedImageIds)->get();
                foreach ($imagesToDelete as $delImg) {
                    $relPath = str_replace('/storage/', '', $delImg->image_url);
                    Storage::disk('public')->delete($relPath);
                    $delImg->delete();
                }
            }

            // 2. Manage primary image selection
            $primaryImageId = $request->input('primary_image_id');
            $primaryNewIndex = $request->has('primary_new_image_index') && $request->input('primary_new_image_index') !== '' && $request->input('primary_new_image_index') !== null
                ? (int) $request->input('primary_new_image_index')
                : null;

            if ($primaryImageId) {
                $product->images()->update(['is_primary' => false]);
                $product->images()->where('id', $primaryImageId)->update(['is_primary' => true]);
            } elseif ($primaryNewIndex !== null) {
                $product->images()->update(['is_primary' => false]);
            }

            // 3. Save newly uploaded images
            $currentMaxSort = $product->images()->max('sort_order') ?? 0;
            foreach ($uploadedFiles as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = 'prod_'.$product->id.'_'.time().'_'.$index.'.'.$file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');

                    $isPrimary = ($primaryNewIndex !== null && $primaryNewIndex === $index)
                        || (! $primaryImageId && $index === 0 && $product->images()->where('is_primary', true)->doesntExist());

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => Storage::url($path),
                        'is_primary' => (bool) $isPrimary,
                        'sort_order' => $currentMaxSort + $index + 1,
                    ]);
                }
            }

            // Ensure at least one image is primary if images exist
            if ($product->images()->exists() && $product->images()->where('is_primary', true)->doesntExist()) {
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }

            // In Edit mode: attributes are locked. Only update price, cost_price, and stock_quantity of variants
            $variantsData = $request->input('variants', []);
            if (! empty($variantsData) && is_array($variantsData)) {
                foreach ($variantsData as $vData) {
                    if (! empty($vData['id'])) {
                        $variant = ProductVariant::where('product_id', $product->id)->find($vData['id']);
                        if ($variant) {
                            $variant->update([
                                'price' => ! empty($vData['price']) ? (float) $vData['price'] : $product->base_price,
                                'cost_price' => ! empty($vData['cost_price']) ? (float) $vData['cost_price'] : null,
                                'stock_quantity' => isset($vData['stock_quantity']) ? (int) $vData['stock_quantity'] : $variant->stock_quantity,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật sản phẩm ['.$product->code.'] thành công!',
                    'product' => $product->load(['category', 'images', 'variants.attributeValues']),
                ]);
            }

            return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm ['.$product->code.'] thành công!');
        } catch (\Throwable $th) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi cập nhật: '.$th->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Lỗi cập nhật: '.$th->getMessage());
        }
    }

    /**
     * Toggle product status (active/inactive) via AJAX
     */
    public function toggleStatus(Request $request, Product $product): JsonResponse
    {
        $product->status = ! $product->status;
        $product->save();

        return response()->json([
            'success' => true,
            'status' => $product->status,
            'message' => 'Đã '.($product->status ? 'kích hoạt' : 'ẩn').' sản phẩm ['.$product->code.'].',
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        $code = $product->code;
        $product->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa sản phẩm ['.$code.'] thành công!',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm ['.$code.'] thành công!');
    }
}
