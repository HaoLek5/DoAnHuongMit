<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'sepay_id',
        'transaction_code',
        'dedup_hash',
        'bank_payment_session_id',
        'order_id',
        'gateway',
        'account_number_last4',
        'transfer_type',
        'transfer_code',
        'expected_amount',
        'received_amount',
        'difference_amount',
        'status',
        'reconciliation_status',
        'raw_content',
        'note',
        'occurred_at',
        'reconciled_by',
        'reconciled_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BankPaymentSession::class, 'bank_payment_session_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'matched' => 'Khớp đơn hàng',
            'overpaid' => 'Chuyển thừa tiền',
            'underpaid' => 'Chuyển thiếu tiền',
            'expired_late' => 'Chuyển sau khi hết hạn QR',
            'session_cancelled' => 'Phiên QR đã bị hủy',
            'unmatched_code' => 'Chưa khớp mã phiên HM',
            'duplicate_tx' => 'Chuyển tiền trùng trên phiên đã trả',
            'out_of_stock' => 'Hết tồn kho khi tiền về',
            'replay_blocked' => 'Chặn dùng lại mã giao dịch',
            'error' => 'Lỗi xử lý đơn',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'matched' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'overpaid' => 'bg-amber-50 text-amber-800 border-amber-200',
            'underpaid', 'expired_late', 'session_cancelled', 'duplicate_tx', 'out_of_stock' => 'bg-rose-50 text-rose-700 border-rose-200',
            'unmatched_code' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }

    public function getReconciliationLabelAttribute(): string
    {
        return match ($this->reconciliation_status) {
            'auto_matched' => 'Khớp tự động',
            'needs_review' => 'Cần đối soát',
            'resolved' => 'Đã đối soát',
            'ignored' => 'Bỏ qua (Chặn replay)',
            default => ucfirst((string) $this->reconciliation_status),
        };
    }
}
