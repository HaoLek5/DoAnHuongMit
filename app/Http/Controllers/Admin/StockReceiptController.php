<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReceiptController extends Controller
{
    /**
     * Danh sách phiếu nhập kho kèm bộ lọc và thống kê
     */
    public function index(Request $request)
    {
        $query = StockReceipt::with([
            'user',
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ])->latest('id');

        // Tìm kiếm theo mã phiếu (PN0001...), tên nhà cung cấp, số điện thoại
        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhere('supplier_phone', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");

                if (preg_match('/^PN(\d+)$/i', $search, $matches)) {
                    $q->orWhere('id', (int) $matches[1]);
                }
            });
        }

        // Lọc theo khoảng ngày nhập
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('receipt_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('receipt_date', '<=', $dateTo);
        }

        // Lọc theo trạng thái
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $receipts = $query->paginate(12)->withQueryString();

        // Thống kê nhanh toàn bộ kho
        $totalReceiptsCount = StockReceipt::count();
        $totalQuantitySum = StockReceipt::where('status', '!=', 'cancelled')->sum('total_quantity');
        $totalAmountSum = StockReceipt::where('status', '!=', 'cancelled')->sum('total_amount');

        // Danh sách sản phẩm và các biến thể để nạp vào Modal thêm/sửa phiếu
        $products = Product::with([
            'category',
            'primaryImage',
            'variants.attributeValues.attribute',
        ])->where('status', true)->orderBy('name')->get();

        // Biến đổi sản phẩm + biến thể thành mảng phẳng tiện lợi cho Kiot tìm kiếm
        $selectableVariants = [];
        foreach ($products as $p) {
            foreach ($p->variants as $v) {
                $selectableVariants[] = [
                    'product_id' => $p->id,
                    'variant_id' => $v->id,
                    'product_code' => $p->code,
                    'product_name' => $p->name,
                    'category_name' => $p->category->name ?? '',
                    'variant_title' => $v->variant_title,
                    'image_url' => $p->primary_image_url ?? '',
                    'stock_quantity' => $v->stock_quantity,
                    'cost_price' => (float) ($v->cost_price ?? $p->base_price * 0.7),
                    'selling_price' => (float) $v->price,
                ];
            }
        }

        $nextCode = StockReceipt::getNextCode();

        return view('admin.stock_receipts.index', compact(
            'receipts',
            'totalReceiptsCount',
            'totalQuantitySum',
            'totalAmountSum',
            'selectableVariants',
            'nextCode'
        ));
    }

    /**
     * Lấy chi tiết phiếu nhập dạng JSON để nạp vào form sửa hoặc xem chi tiết
     */
    public function show(StockReceipt $stockReceipt): JsonResponse
    {
        $stockReceipt->load([
            'user',
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ]);

        return response()->json([
            'success' => true,
            'receipt' => $stockReceipt,
        ]);
    }

    /**
     * Lưu phiếu nhập kho mới và cập nhật số lượng tồn kho + lưu vào inventory_transactions
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:200'],
            'supplier_phone' => ['nullable', 'string', 'max:30'],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập.',
            'items.min' => 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập.',
            'items.*.quantity.min' => 'Số lượng nhập phải lớn hơn hoặc bằng 1.',
            'items.*.unit_cost.min' => 'Đơn giá nhập không được nhỏ hơn 0.',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Khóa các biến thể theo thứ tự ID tăng dần trước khi cấp mã để tránh deadlock với checkout
            $variantIds = collect($validated['items'])
                ->pluck('product_variant_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $lockedVariants = ProductVariant::whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $code = StockReceipt::allocateNextCode();

            $receipt = StockReceipt::create([
                'code' => $code,
                'user_id' => $user ? $user->id : null,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'supplier_phone' => $validated['supplier_phone'] ?? null,
                'receipt_date' => ! empty($validated['receipt_date']) ? $validated['receipt_date'] : now()->toDateString(),
                'total_quantity' => 0,
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed',
            ]);

            $totalQty = 0;
            $totalAmount = 0;

            foreach ($validated['items'] as $itemData) {
                $qty = (int) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $lineTotal = $qty * $unitCost;
                $itemNote = ! empty($itemData['notes']) ? (string) $itemData['notes'] : null;

                $totalQty += $qty;
                $totalAmount += $lineTotal;

                StockReceiptItem::create([
                    'stock_receipt_id' => $receipt->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'notes' => $itemNote,
                ]);

                // Cộng tồn kho trên biến thể đã khóa
                $variant = $lockedVariants->get((int) $itemData['product_variant_id']);
                if ($variant) {
                    $variant->increment('stock_quantity', $qty);
                    if ($unitCost > 0) {
                        $variant->cost_price = $unitCost;
                        $variant->save();
                    }

                    // Ghi lịch sử giao dịch kho vào inventory_transactions
                    InventoryTransaction::create([
                        'variant_id' => $variant->id,
                        'type' => 'import',
                        'quantity' => $qty,
                        'reference_type' => 'purchase_receipt',
                        'reference_id' => $receipt->id,
                        'note' => 'Nhập kho phiếu '.$code.($itemNote ? ' ('.$itemNote.')' : ''),
                        'created_by' => $user ? $user->id : null,
                    ]);
                }
            }

            $receipt->update([
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Tạo phiếu nhập [{$code}] thành công! Đã tăng {$totalQty} sản phẩm vào kho.",
                    'receipt' => $receipt->load(['user', 'items.product', 'items.variant']),
                ]);
            }

            return redirect()->route('admin.stock-receipts.index')
                ->with('success', "Tạo phiếu nhập [{$code}] thành công! Đã tăng {$totalQty} sản phẩm vào kho.");

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi lưu phiếu nhập: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Lỗi khi lưu phiếu nhập: '.$e->getMessage());
        }
    }

    /**
     * Sửa phiếu nhập kho, điều chỉnh tồn kho chênh lệch và ghi nhận vào inventory_transactions
     */
    public function update(Request $request, StockReceipt $stockReceipt)
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:200'],
            'supplier_phone' => ['nullable', 'string', 'max:30'],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Phiếu nhập phải có ít nhất 1 sản phẩm.',
            'items.min' => 'Phiếu nhập phải có ít nhất 1 sản phẩm.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn hoặc bằng 1.',
            'items.*.unit_cost.min' => 'Đơn giá không được nhỏ hơn 0.',
        ]);

        try {
            DB::beginTransaction();

            $stockReceipt = StockReceipt::where('id', $stockReceipt->id)->lockForUpdate()->firstOrFail();
            $user = auth()->user();

            // Lấy danh sách item cũ và khóa toàn bộ biến thể liên quan theo ID tăng dần
            $oldItems = $stockReceipt->items()->with(['product', 'variant'])->get()->keyBy('id');

            $newItemsData = $validated['items'];
            $submittedItemIds = collect($newItemsData)->pluck('id')->filter()->all();

            $allVariantIds = $oldItems->pluck('product_variant_id')
                ->merge(collect($newItemsData)->pluck('product_variant_id'))
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $lockedVariants = ProductVariant::whereIn('id', $allVariantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 1. Xử lý các item cũ bị xóa khỏi phiếu
            foreach ($oldItems as $oldId => $oldItem) {
                if (! in_array($oldId, $submittedItemIds)) {
                    $variant = $lockedVariants->get((int) $oldItem->product_variant_id);
                    if ($variant) {
                        $affected = ProductVariant::where('id', $variant->id)
                            ->where('stock_quantity', '>=', $oldItem->quantity)
                            ->decrement('stock_quantity', $oldItem->quantity);

                        if ($affected !== 1) {
                            throw new \RuntimeException('Không thể xóa dòng nhập kho vì tồn kho hiện tại không đủ để hoàn trừ.');
                        }

                        // Ghi nhận điều chỉnh kho
                        InventoryTransaction::create([
                            'variant_id' => $variant->id,
                            'type' => 'adjustment',
                            'quantity' => -$oldItem->quantity,
                            'reference_type' => 'purchase_receipt',
                            'reference_id' => $stockReceipt->id,
                            'note' => 'Xóa SP khỏi phiếu nhập '.$stockReceipt->code,
                            'created_by' => $user ? $user->id : null,
                        ]);
                    }
                    $oldItem->delete();
                }
            }

            // 2. Cập nhật hoặc thêm mới các item
            $newTotalQty = 0;
            $newTotalAmount = 0;

            foreach ($newItemsData as $itemData) {
                $qty = (int) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $lineTotal = $qty * $unitCost;

                $newTotalQty += $qty;
                $newTotalAmount += $lineTotal;

                $variant = $lockedVariants->get((int) $itemData['product_variant_id']);

                if (! empty($itemData['id']) && isset($oldItems[$itemData['id']])) {
                    $existingItem = $oldItems[$itemData['id']];
                    $qtyDiff = $qty - $existingItem->quantity;

                    if ($qtyDiff !== 0 && $variant) {
                        if ($qtyDiff > 0) {
                            $variant->increment('stock_quantity', $qtyDiff);
                        } else {
                            $deduct = abs($qtyDiff);
                            $affected = ProductVariant::where('id', $variant->id)
                                ->where('stock_quantity', '>=', $deduct)
                                ->decrement('stock_quantity', $deduct);
                            if ($affected !== 1) {
                                throw new \RuntimeException('Tồn kho hiện tại không đủ để giảm số lượng trên phiếu nhập.');
                            }
                        }

                        // Ghi nhận giao dịch kho
                        InventoryTransaction::create([
                            'variant_id' => $variant->id,
                            'type' => $qtyDiff > 0 ? 'import' : 'adjustment',
                            'quantity' => $qtyDiff,
                            'reference_type' => 'purchase_receipt',
                            'reference_id' => $stockReceipt->id,
                            'note' => 'Điều chỉnh số lượng phiếu '.$stockReceipt->code.' ('.($qtyDiff > 0 ? '+' : '').$qtyDiff.')',
                            'created_by' => $user ? $user->id : null,
                        ]);
                    }

                    if ($variant && $unitCost > 0) {
                        $variant->cost_price = $unitCost;
                        $variant->save();
                    }

                    $existingItem->update([
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'total_cost' => $lineTotal,
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                } else {
                    StockReceiptItem::create([
                        'stock_receipt_id' => $stockReceipt->id,
                        'product_id' => $itemData['product_id'],
                        'product_variant_id' => $itemData['product_variant_id'],
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'total_cost' => $lineTotal,
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                    if ($variant) {
                        $variant->increment('stock_quantity', $qty);
                        if ($unitCost > 0) {
                            $variant->cost_price = $unitCost;
                            $variant->save();
                        }

                        InventoryTransaction::create([
                            'variant_id' => $variant->id,
                            'type' => 'import',
                            'quantity' => $qty,
                            'reference_type' => 'purchase_receipt',
                            'reference_id' => $stockReceipt->id,
                            'note' => 'Bổ sung thêm SP vào phiếu '.$stockReceipt->code,
                            'created_by' => $user ? $user->id : null,
                        ]);
                    }
                }
            }

            // Cập nhật phiếu nhập
            $stockReceipt->update([
                'supplier_name' => $validated['supplier_name'] ?? null,
                'supplier_phone' => $validated['supplier_phone'] ?? null,
                'receipt_date' => ! empty($validated['receipt_date']) ? $validated['receipt_date'] : $stockReceipt->receipt_date,
                'total_quantity' => $newTotalQty,
                'total_amount' => $newTotalAmount,
                'notes' => $validated['notes'] ?? null,
                'status' => 'updated',
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Cập nhật phiếu nhập [{$stockReceipt->code}] thành công và đã đồng bộ tồn kho!",
                    'receipt' => $stockReceipt->load(['user', 'items.product', 'items.variant']),
                ]);
            }

            return redirect()->route('admin.stock-receipts.index')
                ->with('success', "Cập nhật phiếu nhập [{$stockReceipt->code}] thành công và đã đồng bộ tồn kho!");

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật phiếu nhập: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Lỗi khi cập nhật phiếu: '.$e->getMessage());
        }
    }

    /**
     * Hủy phiếu nhập kho và hoàn nguyên số lượng tồn kho
     */
    public function destroy(StockReceipt $stockReceipt)
    {
        try {
            DB::beginTransaction();

            $stockReceipt = StockReceipt::where('id', $stockReceipt->id)->with('items')->lockForUpdate()->firstOrFail();
            $user = auth()->user();

            if ($stockReceipt->status === 'cancelled') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu nhập này đã được hủy trước đó.',
                ], 422);
            }

            $variantIds = $stockReceipt->items->pluck('product_variant_id')->unique()->sort()->values();
            $lockedVariants = ProductVariant::whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Hoàn nguyên tồn kho của các biến thể trong phiếu và ghi nhận giao dịch kho
            foreach ($stockReceipt->items as $item) {
                $variant = $lockedVariants->get((int) $item->product_variant_id);
                if ($variant) {
                    $affected = ProductVariant::where('id', $variant->id)
                        ->where('stock_quantity', '>=', $item->quantity)
                        ->decrement('stock_quantity', $item->quantity);

                    if ($affected !== 1) {
                        throw new \RuntimeException('Tồn kho hiện tại không đủ để hủy phiếu nhập kho này.');
                    }

                    InventoryTransaction::create([
                        'variant_id' => $variant->id,
                        'type' => 'adjustment',
                        'quantity' => -$item->quantity,
                        'reference_type' => 'purchase_receipt',
                        'reference_id' => $stockReceipt->id,
                        'note' => 'Hủy phiếu nhập '.$stockReceipt->code,
                        'created_by' => $user ? $user->id : null,
                    ]);
                }
            }

            $stockReceipt->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã hủy phiếu nhập [{$stockReceipt->code}] và hoàn trừ tồn kho thành công!",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy phiếu: '.$e->getMessage(),
            ], 422);
        }
    }
}
