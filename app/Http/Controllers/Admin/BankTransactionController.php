<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BankTransactionController extends Controller
{
    /**
     * Danh sách giao dịch chuyển khoản thực nhận từ SePay / ngân hàng để Admin đối soát.
     */
    public function index(Request $request): View
    {
        $reconciliation = (string) $request->input('reconciliation', 'all');
        $statusFilter = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $query = BankTransaction::with(['session.user', 'order', 'reconciler'])
            ->latest('id');

        if ($reconciliation !== '' && $reconciliation !== 'all' && in_array($reconciliation, ['needs_review', 'auto_matched', 'resolved', 'ignored'], true)) {
            $query->where('reconciliation_status', $reconciliation);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('transfer_code', 'like', "%{$search}%")
                    ->orWhere('transaction_code', 'like', "%{$search}%")
                    ->orWhere('sepay_id', 'like', "%{$search}%")
                    ->orWhere('raw_content', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search): void {
                        $oq->where('order_code', 'like', "%{$search}%");
                    });
            });
        }

        $counts = [
            'all' => BankTransaction::count(),
            'needs_review' => BankTransaction::where('reconciliation_status', 'needs_review')->count(),
            'auto_matched' => BankTransaction::where('reconciliation_status', 'auto_matched')->count(),
            'resolved' => BankTransaction::where('reconciliation_status', 'resolved')->count(),
        ];

        $needsReviewAmount = (float) BankTransaction::where('reconciliation_status', 'needs_review')
            ->sum('received_amount');

        $transactions = $query->paginate(20)->withQueryString();

        return view('admin.bank_transactions.index', compact(
            'transactions',
            'counts',
            'needsReviewAmount',
            'reconciliation',
            'statusFilter',
            'search'
        ));
    }

    /**
     * Đánh dấu đã đối soát / cập nhật ghi chú xử lý cho giao dịch ngân hàng.
     */
    public function reconcile(Request $request, BankTransaction $bankTransaction): RedirectResponse
    {
        $validated = $request->validate([
            'reconciliation_status' => ['required', 'in:resolved,needs_review'],
            'order_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $orderId = $bankTransaction->order_id;
        if (! empty($validated['order_code'])) {
            $matchedOrder = Order::where('order_code', trim((string) $validated['order_code']))->first();
            if (! $matchedOrder) {
                return redirect()->back()->with('error', 'Không tìm thấy mã đơn hàng '.trim((string) $validated['order_code']).' để gắn đối soát.');
            }
            $orderId = $matchedOrder->id;
        }

        $newNote = $bankTransaction->note;
        if (! empty($validated['note'])) {
            $prefix = $newNote ? $newNote."\n" : '';
            $actor = Auth::user()?->name ?? 'Admin';
            $newNote = $prefix.'['.now()->format('d/m/Y H:i').' '.$actor.']: '.trim((string) $validated['note']);
        }

        $isResolved = $validated['reconciliation_status'] === 'resolved';

        $bankTransaction->update([
            'order_id' => $orderId,
            'reconciliation_status' => $validated['reconciliation_status'],
            'reconciled_by' => $isResolved ? Auth::id() : null,
            'reconciled_at' => $isResolved ? now() : null,
            'note' => $newNote,
        ]);

        return redirect()->back()->with('success', $isResolved
            ? 'Đã đánh dấu giao dịch chuyển khoản là Đã đối soát.'
            : 'Đã chuyển giao dịch về trạng thái Cần đối soát.'
        );
    }
}
