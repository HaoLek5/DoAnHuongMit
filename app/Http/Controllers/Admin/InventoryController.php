<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Trang kiểm kho & Sổ kho lịch sử biến động
     */
    public function index(Request $request)
    {
        // 1. Dữ liệu Danh sách Kiểm kho (Lịch sử biến động inventory_transactions)
        $txQuery = InventoryTransaction::with([
            'variant.product.primaryImage',
            'variant.attributeValues.attribute',
            'creator',
        ])->latest('created_at')->latest('id');

        // Lọc theo loại biến động (import, sale, return, adjustment)
        if ($type = $request->input('type')) {
            $txQuery->where('type', $type);
        }

        // Lọc theo chứng từ (purchase_receipt, order, manual)
        if ($refType = $request->input('reference_type')) {
            $txQuery->where('reference_type', $refType);
        }

        // Tìm kiếm theo Mã kiểm kho (KK...), Mã hàng (SP...), Tên hàng hoặc Ghi chú
        if ($search = trim($request->input('search'))) {
            $txQuery->where(function ($query) use ($search) {
                // Kiểm tra mã kiểm kho dạng KK000816 hoặc số ID
                if (preg_match('/^KK(\d+)$/i', $search, $matches)) {
                    $query->where('id', (int) $matches[1]);
                } elseif (is_numeric($search)) {
                    $query->where('id', (int) $search);
                } else {
                    $query->where('note', 'like', "%{$search}%")
                        ->orWhereHas('variant.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                            if (preg_match('/^SP(\d+)$/i', $search, $spMatches)) {
                                $q->orWhere('id', (int) $spMatches[1]);
                            }
                        });
                }
            });
        }

        // Lọc theo khoảng ngày
        if ($dateFrom = $request->input('date_from')) {
            $txQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $txQuery->whereDate('created_at', '<=', $dateTo);
        }

        $transactions = $txQuery->paginate(15, ['*'], 'tx_page')->withQueryString();

        // 2. Thống kê theo chuẩn nghiệp vụ Kiểm kho Kiot
        $totalTransactionsCount = InventoryTransaction::count();
        $totalIncSum = (int) InventoryTransaction::where('quantity', '>', 0)->sum('quantity');
        $totalDecSum = (int) abs(InventoryTransaction::where('quantity', '<', 0)->sum('quantity'));
        $totalDiffSum = (int) InventoryTransaction::sum('quantity');

        return view('admin.inventory.index', compact(
            'transactions',
            'totalTransactionsCount',
            'totalIncSum',
            'totalDecSum',
            'totalDiffSum'
        ));
    }

    /**
     * Thực hiện cân bằng kho sau khi kiểm kê (Lưu vào inventory_transactions với type = adjustment)
     */
    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.actual_stock' => ['required', 'integer', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Vui lòng chọn ít nhất 1 sản phẩm để kiểm kho.',
            'items.min' => 'Vui lòng chọn ít nhất 1 sản phẩm để kiểm kho.',
            'items.*.actual_stock.required' => 'Vui lòng nhập tồn kho thực tế.',
            'items.*.actual_stock.min' => 'Tồn kho thực tế không được nhỏ hơn 0.',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $adjustedCount = 0;

            $variantIds = collect($validated['items'])
                ->pluck('variant_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $lockedVariants = ProductVariant::whereIn('id', $variantIds)
                ->with('product')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($validated['items'] as $itemData) {
                $variant = $lockedVariants->get((int) $itemData['variant_id']);
                if (! $variant) {
                    continue;
                }

                $systemStock = $variant->stock_quantity;
                $actualStock = (int) $itemData['actual_stock'];
                $diff = $actualStock - $systemStock;

                // Cập nhật tồn kho thực tế
                $variant->stock_quantity = $actualStock;
                $variant->save();

                // Ghi nhận biến động vào inventory_transactions
                if ($diff !== 0) {
                    InventoryTransaction::create([
                        'variant_id' => $variant->id,
                        'type' => 'adjustment',
                        'quantity' => $diff,
                        'reference_type' => 'manual',
                        'reference_id' => null,
                        'note' => $itemData['note']
                            ? $itemData['note']
                            : ('Kiểm kê cân bằng kho: '.$systemStock.' -> '.$actualStock.' ('.($diff > 0 ? '+' : '').$diff.')'),
                        'created_by' => $user ? $user->id : null,
                    ]);
                    $adjustedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cân bằng kho thành công cho '.count($validated['items'])." sản phẩm! Đã ghi nhận biến động {$adjustedCount} mặt hàng.",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cân bằng kho: '.$e->getMessage(),
            ], 422);
        }
    }
}
