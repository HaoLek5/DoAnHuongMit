<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Customer Home / Shop Page
     */
    public function index(Request $request)
    {
        $query = Product::where('status', true)
            ->with([
                'category',
                'primaryImage',
                'images',
                'activeSaleItems.campaign',
                'variants.attributeValues.attribute',
                'variants.activeSaleItems.campaign',
            ]);

        // Search by product name or product code (SP0001...)
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

        // Sorting
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('base_price', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc'),
            default => $query->latest('id'),
        };

        $products = $query->paginate(12)->withQueryString();

        // Categories with active products count
        $categories = Category::withCount(['products' => function ($q) {
            $q->where('status', true);
        }])->orderBy('name')->get();

        $totalActiveProducts = Product::where('status', true)->count();

        return view('home', compact('products', 'categories', 'totalActiveProducts'));
    }

    /**
     * Get single product detail for quick-view modal via AJAX
     */
    public function getProductJson(Product $product): JsonResponse
    {
        if (! $product->status) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm này hiện đang tạm ẩn.',
            ], 404);
        }

        $product->load([
            'category',
            'primaryImage',
            'images',
            'activeSaleItems.campaign',
            'variants.attributeValues.attribute',
            'variants.activeSaleItems.campaign',
        ]);

        // Extract unique attributes and their values for this product
        $attributes = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->attributeValues as $av) {
                $attr = $av->attribute;
                if (! $attr) {
                    continue;
                }

                if (! isset($attributes[$attr->id])) {
                    $attributes[$attr->id] = [
                        'id' => $attr->id,
                        'name' => $attr->name,
                        'code' => $attr->code,
                        'values' => [],
                    ];
                }

                if (! in_array($av->value, $attributes[$attr->id]['values'])) {
                    $attributes[$attr->id]['values'][] = $av->value;
                }
            }
        }

        return response()->json([
            'success' => true,
            'product' => $product,
            'grouped_attributes' => array_values($attributes),
        ]);
    }
}
