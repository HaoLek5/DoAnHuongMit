<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'supplier_name',
        'supplier_phone',
        'receipt_date',
        'total_quantity',
        'total_amount',
        'notes',
        'status',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_quantity' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    /**
     * Xem trước mã phiếu nhập tiếp theo dạng PN0001, PN0002 (chỉ đọc để hiển thị UI).
     */
    public static function getNextCode(): string
    {
        return CodeSequence::previewNextCode('stock_receipts', 'stock_receipts', 'code', 'PN', 4);
    }

    /**
     * Cấp phát mã phiếu nhập tiếp theo an toàn concurrency khi lưu phiếu vào DB.
     */
    public static function allocateNextCode(): string
    {
        return CodeSequence::nextCode('stock_receipts', 'stock_receipts', 'code', 'PN', 4);
    }
}
