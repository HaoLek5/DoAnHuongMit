<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'variant_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tên nhãn tiếng Việt của loại biến động kho
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'import' => 'Nhập kho',
            'sale' => 'Bán hàng',
            'return' => 'Trả hàng',
            'adjustment' => 'Kiểm kho / Điều chỉnh',
            default => ucfirst($this->type),
        };
    }

    /**
     * Tên nhãn tiếng Việt của loại chứng từ
     */
    public function getReferenceTypeLabelAttribute(): string
    {
        return match ($this->reference_type) {
            'purchase_receipt' => 'Phiếu nhập',
            'order' => 'Đơn hàng',
            'manual' => 'Thủ công',
            default => '—',
        };
    }

    /**
     * Mã kiểm kho dạng KK000816 giống Kiot
     */
    public function getCodeAttribute(): string
    {
        return 'KK'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * SL lệch tăng (nếu quantity > 0)
     */
    public function getIncQuantityAttribute(): int
    {
        return $this->quantity > 0 ? $this->quantity : 0;
    }

    /**
     * SL lệch giảm (nếu quantity < 0)
     */
    public function getDecQuantityAttribute(): int
    {
        return $this->quantity < 0 ? abs($this->quantity) : 0;
    }

    /**
     * Tồn kho hệ thống trước khi cân bằng
     */
    public function getSystemStockAttribute(): int
    {
        if (preg_match('/Kiểm kê cân bằng kho:\s*(\d+)\s*->\s*(\d+)/', $this->note ?? '', $m)) {
            return (int) $m[1];
        }

        $actual = $this->actual_stock;

        return max(0, $actual - $this->quantity);
    }

    /**
     * Tồn kho thực tế sau khi cân bằng
     */
    public function getActualStockAttribute(): int
    {
        if (preg_match('/Kiểm kê cân bằng kho:\s*(\d+)\s*->\s*(\d+)/', $this->note ?? '', $m)) {
            return (int) $m[2];
        }

        return $this->variant ? (int) $this->variant->stock_quantity : abs($this->quantity);
    }
}
