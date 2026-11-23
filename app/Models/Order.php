<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'address_id',
        'receiver_name',
        'receiver_phone',
        'shipping_address',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'total_amount',
        'payment_method',
        'payment_status',
        'deposit_required',
        'deposit_amount',
        'paid_amount',
        'remaining_amount',
        'order_status',
        'idempotency_key',
        'note',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_required' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected $appends = [
        'status_label',
        'payment_method_label',
        'payment_status_label',
        'shipping_carrier',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (empty($order->order_code)) {
                $order->order_code = self::generateOrderCode();
            }
        });
    }

    public static function generateOrderCode(): string
    {
        return CodeSequence::nextCode('orders', 'orders', 'order_code', 'DH', 4);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'Đã đặt hàng',
            'confirmed' => 'Đã xác nhận',
            'cancel_pending' => 'Chờ hủy',
            'processing' => 'Đang đóng gói',
            'shipping' => 'Đang giao hàng',
            'delivered' => 'Giao thành công',
            'cancelled' => 'Đã hủy',
            default => ucfirst((string) $this->order_status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200',
            'cancel_pending' => 'bg-rose-50 text-rose-700 border-rose-200',
            'processing' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'shipping' => 'bg-purple-50 text-purple-700 border-purple-200',
            'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    public function getShippingCarrierAttribute(): string
    {
        return in_array($this->payment_method, ['cod', 'contact_shop'], true) ? 'GHTK' : 'VTP';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'contact_shop' => 'Liên hệ shop',
            default => strtoupper((string) $this->payment_method),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'deposit_pending' => 'Chờ đặt cọc',
            'deposit_paid' => 'Đã đặt cọc',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
            default => $this->payment_status ? ucfirst((string) $this->payment_status) : 'Chưa cập nhật',
        };
    }
}
