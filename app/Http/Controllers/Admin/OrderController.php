<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng trong khu vực quản trị
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.variant.product.primaryImage', 'payments'])
            ->latest('id');

        // Tìm kiếm theo mã đơn, người nhận, số điện thoại
        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái đơn hàng (pending, confirmed, cancel_pending, delivered, cancelled)
        if ($status = $request->input('status')) {
            if (in_array($status, ['pending', 'confirmed', 'cancel_pending', 'processing', 'shipping', 'delivered', 'cancelled'], true)) {
                $query->where('order_status', $status);
            }
        }

        // Lọc theo khoảng ngày
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Đếm số lượng theo trạng thái
        $counts = [
            'all' => Order::count(),
            'pending' => Order::where('order_status', 'pending')->count(),
            'confirmed' => Order::where('order_status', 'confirmed')->count(),
            'delivered' => Order::where('order_status', 'delivered')->count(),
            'cancel_pending' => Order::where('order_status', 'cancel_pending')->count(),
            'cancelled' => Order::where('order_status', 'cancelled')->count(),
        ];

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders', 'counts'));
    }

    /**
     * Chi tiết đơn hàng
     */
    public function show(Order $order)
    {
        $order->load([
            'user',
            'address',
            'items.variant.product.primaryImage',
            'payments.confirmer',
            'bankTransactions.reconciler',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Cập nhật trạng thái đơn hàng (kèm tự động xử lý tồn kho)
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => ['required', 'in:pending,confirmed,cancel_pending,processing,shipping,delivered,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = $validated['order_status'];

        try {
            $unchanged = DB::transaction(function () use ($order, $newStatus, $validated): bool {
                /** @var Order $lockedOrder */
                $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);
                $oldStatus = $lockedOrder->order_status;

                if ($newStatus === $oldStatus) {
                    return true;
                }

                // Khóa tất cả biến thể liên quan theo thứ tự tăng dần để tránh deadlock và double restock
                $variantIds = $lockedOrder->items->pluck('variant_id')->filter()->unique()->sort()->values();
                $lockedVariants = ProductVariant::whereIn('id', $variantIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // 1. Nếu hủy đơn hàng (từ trạng thái chưa hủy sang cancelled): Hoàn trả tồn kho đúng 1 lần
                if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                    foreach ($lockedOrder->items as $item) {
                        if ($item->variant_id && $lockedVariants->has($item->variant_id)) {
                            ProductVariant::where('id', $item->variant_id)
                                ->increment('stock_quantity', $item->quantity);

                            InventoryTransaction::create([
                                'variant_id' => $item->variant_id,
                                'type' => 'return',
                                'quantity' => $item->quantity,
                                'reference_type' => 'order',
                                'reference_id' => $lockedOrder->id,
                                'note' => ! empty($validated['admin_note']) ? $validated['admin_note'] : "Quản trị viên hủy đơn hàng #{$lockedOrder->order_code} - Hoàn tồn kho",
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    }
                }

                // 2. Nếu khôi phục đơn hàng (từ cancelled sang trạng thái khác): Trừ lại tồn kho có kiểm tra
                if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                    foreach ($lockedOrder->items as $item) {
                        if ($item->variant_id && $lockedVariants->has($item->variant_id)) {
                            $affected = ProductVariant::where('id', $item->variant_id)
                                ->where('stock_quantity', '>=', $item->quantity)
                                ->decrement('stock_quantity', $item->quantity);

                            if ($affected !== 1) {
                                throw new \Exception("Không đủ tồn kho cho sản phẩm '{$item->product_name}' ({$item->variant_name}) để khôi phục đơn hàng.");
                            }

                            InventoryTransaction::create([
                                'variant_id' => $item->variant_id,
                                'type' => 'sale',
                                'quantity' => -$item->quantity, // Phải là số âm vì xuất bán làm giảm tồn kho
                                'reference_type' => 'order',
                                'reference_id' => $lockedOrder->id,
                                'note' => "Khôi phục đơn hàng #{$lockedOrder->order_code} - Xuất kho bán hàng",
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    }
                }

                // 3. Nếu giao thành công và đơn là COD (chưa thanh toán hoặc mới cọc 40%): Tự động tất toán phần còn lại
                $updateData = [
                    'order_status' => $newStatus,
                ];

                if ($newStatus === 'delivered' && $lockedOrder->payment_method === 'cod' && in_array($lockedOrder->payment_status, ['unpaid', 'deposit_paid'], true)) {
                    $remainingToCollect = (float) $lockedOrder->remaining_amount;
                    $updateData['payment_status'] = 'paid';
                    $updateData['paid_amount'] = $lockedOrder->total_amount;
                    $updateData['remaining_amount'] = 0;

                    if ($lockedOrder->payment_status === 'unpaid') {
                        $pendingUpdated = $lockedOrder->payments()
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'paid',
                                'paid_at' => now(),
                                'confirmed_by' => Auth::id(),
                                'confirmed_at' => now(),
                                'note' => 'Đã thu tiền COD khi giao hàng thành công',
                            ]);

                        if ($pendingUpdated === 0 && $remainingToCollect > 0) {
                            Payment::create([
                                'order_id' => $lockedOrder->id,
                                'payment_type' => 'final',
                                'payment_method' => 'cod',
                                'amount' => $remainingToCollect,
                                'status' => 'paid',
                                'paid_at' => now(),
                                'confirmed_by' => Auth::id(),
                                'confirmed_at' => now(),
                                'note' => 'Đã thu tiền COD khi giao hàng thành công',
                            ]);
                        }
                    } elseif ($remainingToCollect > 0 && ! $lockedOrder->payments()->where('payment_type', 'final')->where('status', 'paid')->exists()) {
                        Payment::create([
                            'order_id' => $lockedOrder->id,
                            'payment_type' => 'final',
                            'payment_method' => 'cod',
                            'amount' => $remainingToCollect,
                            'status' => 'paid',
                            'paid_at' => now(),
                            'confirmed_by' => Auth::id(),
                            'confirmed_at' => now(),
                            'note' => 'Đã thu phần tiền COD còn lại (60%) khi giao hàng thành công',
                        ]);
                    }
                }

                if (! empty($validated['admin_note'])) {
                    $notePrefix = $lockedOrder->note ? $lockedOrder->note."\n" : '';
                    $updateData['note'] = $notePrefix.'['.date('d/m/Y H:i').' Admin]: '.$validated['admin_note'];
                }

                $lockedOrder->update($updateData);

                return false;
            }, 3);

            if ($unchanged) {
                return redirect()->back()->with('info', 'Trạng thái đơn hàng không thay đổi.');
            }

            $order->refresh();

            $msg = ($newStatus === 'cancelled' && ! empty($validated['admin_note']) && str_contains(mb_strtolower($validated['admin_note']), 'hoàn'))
                ? "Đã hoàn đơn hàng #{$order->order_code} và hoàn trả hàng về kho thành công."
                : "Đã cập nhật trạng thái đơn hàng thành: '{$order->status_label}'";

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật trạng thái đơn: '.$e->getMessage());

            return redirect()->back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái thanh toán (Xác nhận thu tiền COD, hoàn tiền, ...)
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:unpaid,paid,refunded'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $status = $validated['payment_status'];

        DB::transaction(function () use ($order, $status, $validated): void {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $updateData = [
                'payment_status' => $status,
            ];

            $paidDepositAmount = (float) $lockedOrder->payments()
                ->where('payment_type', 'deposit')
                ->where('status', 'paid')
                ->sum('amount');

            if ($status === 'paid') {
                $remainingToCollect = (float) $lockedOrder->remaining_amount;
                $wasDepositPaid = $lockedOrder->payment_status === 'deposit_paid' || $paidDepositAmount > 0;
                $updateData['paid_amount'] = $lockedOrder->total_amount;
                $updateData['remaining_amount'] = 0;

                if ($wasDepositPaid) {
                    $finalToCollect = $remainingToCollect > 0
                        ? $remainingToCollect
                        : max(0.0, (float) $lockedOrder->total_amount - $paidDepositAmount);

                    if ($finalToCollect > 0 && ! $lockedOrder->payments()->where('payment_type', 'final')->where('status', 'paid')->exists()) {
                        Payment::create([
                            'order_id' => $lockedOrder->id,
                            'payment_type' => 'final',
                            'payment_method' => 'cod',
                            'amount' => $finalToCollect,
                            'status' => 'paid',
                            'paid_at' => now(),
                            'confirmed_by' => Auth::id(),
                            'confirmed_at' => now(),
                            'note' => $validated['note'] ?? 'Đã thu phần tiền COD còn lại (60%)',
                        ]);
                    }
                } else {
                    $lockedOrder->payments()
                        ->where('payment_type', '!=', 'deposit')
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'confirmed_by' => Auth::id(),
                            'confirmed_at' => now(),
                            'note' => $validated['note'] ?? 'Xác nhận đã thanh toán toàn bộ',
                        ]);
                }
            } elseif ($status === 'unpaid') {
                if ($paidDepositAmount > 0) {
                    // Tuyệt đối không xóa/ghi đè lịch sử tiền cọc VietQR đã xác nhận; chỉ đặt lại phần thu COD cuối kỳ
                    $updateData['payment_status'] = 'deposit_paid';
                    $updateData['paid_amount'] = $paidDepositAmount;
                    $updateData['remaining_amount'] = max(0, (float) $lockedOrder->total_amount - $paidDepositAmount);

                    $lockedOrder->payments()
                        ->where('payment_type', 'final')
                        ->where('payment_method', 'cod')
                        ->update([
                            'status' => 'pending',
                            'paid_at' => null,
                            'confirmed_by' => null,
                            'confirmed_at' => null,
                            'note' => $validated['note'] ?? 'Đặt lại phần COD còn lại chưa thu (giữ nguyên tiền cọc VietQR)',
                        ]);
                } else {
                    $updateData['paid_amount'] = 0;
                    $updateData['remaining_amount'] = $lockedOrder->total_amount;

                    $lockedOrder->payments()->update([
                        'status' => 'pending',
                        'paid_at' => null,
                        'confirmed_by' => null,
                        'confirmed_at' => null,
                        'note' => $validated['note'] ?? 'Đặt lại chưa thanh toán',
                    ]);
                }
            } elseif ($status === 'refunded') {
                $lockedOrder->payments()->update([
                    'status' => 'refunded',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'note' => $validated['note'] ?? 'Đã hoàn tiền cho khách',
                ]);
            }

            $lockedOrder->update($updateData);
        }, 3);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái thanh toán đơn hàng thành công!');
    }

    /**
     * In hóa đơn / phiếu giao hàng (Packing slip) đơn lẻ
     */
    public function printInvoice(Order $order)
    {
        $order->load(['user', 'items.variant.product', 'payments']);
        $orders = collect([$order]);

        return view('admin.orders.print', compact('orders', 'order'));
    }

    /**
     * In hàng loạt phiếu giao hàng cho nhiều đơn
     */
    public function printBulk(Request $request)
    {
        $rawIds = $request->query('ids', '');
        $ids = is_array($rawIds) ? $rawIds : array_filter(explode(',', (string) $rawIds));

        if (empty($ids)) {
            return redirect()->route('admin.orders.index')->with('error', 'Chưa chọn đơn hàng nào để in.');
        }

        $orders = Order::whereIn('id', $ids)
            ->with(['user', 'items.variant.product', 'payments'])
            ->latest('id')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('admin.orders.index')->with('error', 'Không tìm thấy đơn hàng nào tương ứng.');
        }

        return view('admin.orders.print', compact('orders'));
    }

    /**
     * Thao tác hàng loạt: Xác nhận, In đơn, hoặc Xác nhận và in đơn
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'action' => ['required', 'in:confirm,confirm_and_print,print'],
        ]);

        $orderIds = $validated['order_ids'];
        $action = $validated['action'];

        $confirmedCount = 0;
        if ($action === 'confirm' || $action === 'confirm_and_print') {
            $confirmedCount = DB::transaction(function () use ($orderIds): int {
                $sortedIds = collect($orderIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values();
                $lockedPendingOrders = Order::whereIn('id', $sortedIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->where('order_status', 'pending');

                $count = 0;
                foreach ($lockedPendingOrders as $lockedOrder) {
                    $lockedOrder->update(['order_status' => 'confirmed']);
                    $count++;
                }

                return $count;
            }, 3);
        }

        // Nếu là thao tác in hoặc xác nhận & in
        if ($action === 'confirm_and_print' || $action === 'print') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'print_url' => route('admin.orders.print-bulk', ['ids' => implode(',', $orderIds)]),
                    'message' => $action === 'confirm_and_print'
                        ? "Đã xác nhận {$confirmedCount} đơn hàng và mở trang in!"
                        : 'Mở trang in cho các đơn hàng đã chọn!',
                ]);
            }

            // Trả về view in trực tiếp (khi submit form target="_blank")
            $orders = Order::whereIn('id', $orderIds)
                ->with(['user', 'items.variant.product', 'payments'])
                ->latest('id')
                ->get();

            return view('admin.orders.print', compact('orders'));
        }

        return redirect()->back()->with('success', "Đã xác nhận thành công {$confirmedCount} đơn hàng!");
    }
}
