<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'base_price',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected $appends = [
        'code',
        'total_stock',
        'primary_image_url',
        'active_sale_item',
    ];

    public function saleCampaignItems(): HasMany
    {
        return $this->hasMany(SaleCampaignItem::class);
    }

    /**
     * Quan hệ các mục khuyến mãi còn hiệu lực và còn suất giảm giá của sản phẩm.
     */
    public function activeSaleItems(): HasMany
    {
        $now = now();

        return $this->hasMany(SaleCampaignItem::class)
            ->whereColumn('sold_quantity', '<', 'sale_quantity')
            ->whereHas('campaign', function ($q) use ($now) {
                $q->where('is_active', true)
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>=', $now);
            })
            ->with('campaign')
            ->orderBy('sale_price', 'asc');
    }

    /**
     * Active sale campaign item if currently running and quota remaining
     */
    public function getActiveSaleItemAttribute(): ?SaleCampaignItem
    {
        if ($this->relationLoaded('activeSaleItems')) {
            return $this->getRelation('activeSaleItems')->first();
        }

        $now = now();

        $item = SaleCampaignItem::where('product_id', $this->id)
            ->whereColumn('sold_quantity', '<', 'sale_quantity')
            ->whereHas('campaign', function ($q) use ($now) {
                $q->where('is_active', true)
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>=', $now);
            })
            ->with('campaign')
            ->orderBy('sale_price', 'asc')
            ->first();

        $this->setRelation('activeSaleItems', $item ? collect([$item]) : collect());

        return $item;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany([
            'is_primary' => 'max',
            'id' => 'min',
        ]);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->with('attributeValues');
    }

    /**
     * Mã sản phẩm cha dạng SP0001, SP0002...
     */
    public function getCodeAttribute(): string
    {
        return 'SP'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mã sản phẩm tiếp theo
     */
    public static function getNextCode(): string
    {
        $lastId = static::max('id') ?? 0;

        return 'SP'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Tổng số lượng tồn kho của các biến thể
     */
    public function getTotalStockAttribute(): int
    {
        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum('stock_quantity');
        }

        return (int) $this->variants()->sum('stock_quantity');
    }

    /**
     * Đường dẫn ảnh chính
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('images')) {
            if ($this->images->isEmpty()) {
                return null;
            }

            $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

            return $primary ? $primary->image_url : null;
        }

        if ($this->relationLoaded('primaryImage')) {
            return $this->primaryImage?->image_url;
        }

        $image = $this->images()->orderByDesc('is_primary')->first();

        return $image ? $image->image_url : null;
    }
}
