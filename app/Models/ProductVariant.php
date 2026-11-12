<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price',
        'cost_price',
        'stock_quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    protected $appends = [
        'variant_title',
        'active_sale_item',
        'effective_price',
    ];

    public function saleCampaignItems(): HasMany
    {
        return $this->hasMany(SaleCampaignItem::class, 'product_variant_id');
    }

    /**
     * Quan hệ các mục khuyến mãi đang còn hiệu lực và còn số lượng suất giảm giá.
     */
    public function activeSaleItems(): HasMany
    {
        $now = now();

        return $this->hasMany(SaleCampaignItem::class, 'product_variant_id')
            ->whereColumn('sold_quantity', '<', 'sale_quantity')
            ->whereHas('campaign', function ($q) use ($now) {
                $q->where('is_active', true)
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>=', $now);
            })
            ->with('campaign');
    }

    public function getActiveSaleItemAttribute(): ?SaleCampaignItem
    {
        if ($this->relationLoaded('activeSaleItems')) {
            return $this->getRelation('activeSaleItems')->first();
        }

        $now = now();

        $item = SaleCampaignItem::where('product_variant_id', $this->id)
            ->whereColumn('sold_quantity', '<', 'sale_quantity')
            ->whereHas('campaign', function ($q) use ($now) {
                $q->where('is_active', true)
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>=', $now);
            })
            ->with('campaign')
            ->first();

        $this->setRelation('activeSaleItems', $item ? collect([$item]) : collect());

        return $item;
    }

    public function getEffectivePriceAttribute(): float
    {
        $saleItem = $this->active_sale_item;

        return $saleItem ? (float) $saleItem->sale_price : (float) $this->price;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_values',
            'variant_id',
            'attribute_value_id'
        )->with('attribute');
    }

    /**
     * Get descriptive title combining attribute values, e.g. "Vani / 800g"
     */
    public function getVariantTitleAttribute(): string
    {
        if ($this->relationLoaded('attributeValues') && $this->attributeValues->isNotEmpty()) {
            return $this->attributeValues->pluck('value')->implode(' / ');
        }

        return 'Mặc định';
    }
}
