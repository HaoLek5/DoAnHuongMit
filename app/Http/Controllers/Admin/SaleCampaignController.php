<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleCampaign;
use App\Models\SaleCampaignItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleCampaignController extends Controller
{
    /**
     * Display listing of sale campaigns with status tabs and statistics
     */
    public function index(Request $request): View
    {
        $now = now();

        // Calculate counts for tabs
        $allCampaigns = SaleCampaign::all();
        $counts = [
            'total' => $allCampaigns->count(),
            'running' => $allCampaigns->where('is_active', true)->filter(fn ($c) => $now->between($c->start_time, $c->end_time))->count(),
            'upcoming' => $allCampaigns->where('is_active', true)->filter(fn ($c) => $now->lt($c->start_time))->count(),
            'ended' => $allCampaigns->filter(fn ($c) => $now->gt($c->end_time))->count(),
            'paused' => $allCampaigns->where('is_active', false)->count(),
        ];

        $query = SaleCampaign::with([
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ]);

        // Filter by tab status
        $status = $request->input('status');
        if ($status === 'running') {
            $query->where('is_active', true)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now);
        } elseif ($status === 'upcoming') {
            $query->where('is_active', true)
                ->where('start_time', '>', $now);
        } elseif ($status === 'ended') {
            $query->where('end_time', '<', $now);
        } elseif ($status === 'paused') {
            $query->where('is_active', false);
        }

        // Search by name
        if ($search = trim($request->input('search') ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'start_asc' => $query->orderBy('start_time', 'asc'),
            'start_desc' => $query->orderBy('start_time', 'desc'),
            default => $query->latest('id'),
        };

        $campaigns = $query->paginate(10)->withQueryString();

        return view('admin.sale_campaigns.index', compact('campaigns', 'counts'));
    }

    /**
     * Show form for creating a new sale campaign
     */
    public function create(): View
    {
        $products = Product::where('status', true)
            ->with([
                'category',
                'primaryImage',
                'variants.attributeValues.attribute',
            ])
            ->orderBy('name')
            ->get();

        return view('admin.sale_campaigns.create', compact('products'));
    }

    /**
     * Store a newly created sale campaign
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'is_active' => ['nullable'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.sale_price' => ['required', 'numeric', 'min:1000'],
            'items.*.sale_quantity' => ['required', 'integer', 'min:1'],
        ], [
            'name.required' => 'Vui lòng nhập tên chiến dịch sale.',
            'start_time.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'end_time.required' => 'Vui lòng chọn thời gian kết thúc.',
            'end_time.after' => 'Thời gian kết thúc phải diễn ra sau thời gian bắt đầu.',
            'items.required' => 'Vui lòng chọn ít nhất 1 sản phẩm tham gia chiến dịch sale.',
            'items.min' => 'Vui lòng chọn ít nhất 1 sản phẩm tham gia chiến dịch sale.',
            'items.*.sale_price.required' => 'Vui lòng nhập giá sale cho sản phẩm.',
            'items.*.sale_price.min' => 'Giá sale tối thiểu là 1.000 đ.',
            'items.*.sale_quantity.required' => 'Vui lòng nhập số lượng sản phẩm mở bán.',
            'items.*.sale_quantity.min' => 'Số lượng sale tối thiểu là 1 sản phẩm.',
        ]);

        try {
            DB::beginTransaction();

            $campaign = SaleCampaign::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_time' => Carbon::parse($validated['start_time']),
                'end_time' => Carbon::parse($validated['end_time']),
                'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
            ]);

            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::findOrFail($itemData['product_variant_id']);

                SaleCampaignItem::create([
                    'sale_campaign_id' => $campaign->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $variant->id,
                    'original_price' => $variant->price,
                    'sale_price' => $itemData['sale_price'],
                    'sale_quantity' => $itemData['sale_quantity'],
                    'sold_quantity' => 0,
                ]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo chiến dịch sale "'.$campaign->name.'" thành công!',
                    'redirect' => route('admin.sale-campaigns.index'),
                ]);
            }

            return redirect()->route('admin.sale-campaigns.index')->with('success', 'Tạo chiến dịch sale "'.$campaign->name.'" thành công!');
        } catch (\Throwable $th) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi tạo chiến dịch: '.$th->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Lỗi tạo chiến dịch: '.$th->getMessage());
        }
    }

    /**
     * Show form for editing an existing sale campaign
     */
    public function edit(SaleCampaign $saleCampaign): View
    {
        $saleCampaign->load([
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ]);

        $products = Product::where('status', true)
            ->with([
                'category',
                'primaryImage',
                'variants.attributeValues.attribute',
            ])
            ->orderBy('name')
            ->get();

        return view('admin.sale_campaigns.edit', [
            'campaign' => $saleCampaign,
            'products' => $products,
        ]);
    }

    /**
     * Update an existing sale campaign
     */
    public function update(Request $request, SaleCampaign $saleCampaign)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'is_active' => ['nullable'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.sale_price' => ['required', 'numeric', 'min:1000'],
            'items.*.sale_quantity' => ['required', 'integer', 'min:1'],
        ], [
            'name.required' => 'Vui lòng nhập tên chiến dịch sale.',
            'start_time.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'end_time.required' => 'Vui lòng chọn thời gian kết thúc.',
            'end_time.after' => 'Thời gian kết thúc phải diễn ra sau thời gian bắt đầu.',
            'items.required' => 'Vui lòng chọn ít nhất 1 sản phẩm tham gia chiến dịch sale.',
            'items.min' => 'Vui lòng chọn ít nhất 1 sản phẩm tham gia chiến dịch sale.',
            'items.*.sale_price.required' => 'Vui lòng nhập giá sale cho sản phẩm.',
            'items.*.sale_price.min' => 'Giá sale tối thiểu là 1.000 đ.',
            'items.*.sale_quantity.required' => 'Vui lòng nhập số lượng sản phẩm mở bán.',
            'items.*.sale_quantity.min' => 'Số lượng sale tối thiểu là 1 sản phẩm.',
        ]);

        try {
            DB::beginTransaction();

            $saleCampaign->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_time' => Carbon::parse($validated['start_time']),
                'end_time' => Carbon::parse($validated['end_time']),
                'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : false,
            ]);

            // Track existing items to keep sold_quantity if still present
            $existingItems = $saleCampaign->items->keyBy('product_variant_id');
            $submittedVariantIds = [];

            foreach ($validated['items'] as $itemData) {
                $variantId = $itemData['product_variant_id'];
                $submittedVariantIds[] = $variantId;
                $variant = ProductVariant::findOrFail($variantId);

                if ($existingItems->has($variantId)) {
                    $item = $existingItems->get($variantId);
                    $item->update([
                        'sale_price' => $itemData['sale_price'],
                        'sale_quantity' => max($itemData['sale_quantity'], $item->sold_quantity),
                    ]);
                } else {
                    SaleCampaignItem::create([
                        'sale_campaign_id' => $saleCampaign->id,
                        'product_id' => $itemData['product_id'],
                        'product_variant_id' => $variant->id,
                        'original_price' => $variant->price,
                        'sale_price' => $itemData['sale_price'],
                        'sale_quantity' => $itemData['sale_quantity'],
                        'sold_quantity' => 0,
                    ]);
                }
            }

            // Remove items no longer in campaign
            $saleCampaign->items()->whereNotIn('product_variant_id', $submittedVariantIds)->delete();

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật chiến dịch sale "'.$saleCampaign->name.'" thành công!',
                    'redirect' => route('admin.sale-campaigns.index'),
                ]);
            }

            return redirect()->route('admin.sale-campaigns.index')->with('success', 'Cập nhật chiến dịch sale "'.$saleCampaign->name.'" thành công!');
        } catch (\Throwable $th) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi cập nhật chiến dịch: '.$th->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Lỗi cập nhật chiến dịch: '.$th->getMessage());
        }
    }

    /**
     * Delete a sale campaign
     */
    public function destroy(SaleCampaign $saleCampaign)
    {
        try {
            $name = $saleCampaign->name;
            $saleCampaign->delete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa chiến dịch sale "'.$name.'" thành công!',
                ]);
            }

            return redirect()->route('admin.sale-campaigns.index')->with('success', 'Đã xóa chiến dịch sale "'.$name.'" thành công!');
        } catch (\Throwable $th) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa chiến dịch: '.$th->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Không thể xóa chiến dịch: '.$th->getMessage());
        }
    }

    /**
     * Toggle campaign active/paused status via AJAX
     */
    public function toggleStatus(SaleCampaign $saleCampaign): JsonResponse
    {
        try {
            $saleCampaign->is_active = ! $saleCampaign->is_active;
            $saleCampaign->save();

            $statusText = $saleCampaign->is_active ? 'Đã kích hoạt' : 'Đã tạm dừng';

            return response()->json([
                'success' => true,
                'is_active' => $saleCampaign->is_active,
                'status' => $saleCampaign->status,
                'status_label' => $saleCampaign->status_label,
                'message' => "{$statusText} chiến dịch sale \"{$saleCampaign->name}\".",
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cập nhật trạng thái: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * API helper to get products with variants formatted for campaign picker
     */
    public function getProductsJson(): JsonResponse
    {
        $products = Product::where('status', true)
            ->with([
                'category',
                'primaryImage',
                'variants.attributeValues.attribute',
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }
}
