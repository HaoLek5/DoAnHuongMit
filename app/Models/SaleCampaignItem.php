<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleCampaignItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_campaign_id',
        'product_id',
        'product_variant_id',
        'original_price',
        'sale_price',
        'sale_quantity',
        'sold_quantity',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_quantity' => 'integer',
        'sold_quantity' => 'integer',
    ];

    protected $appends = [
        'remaining_quantity',
        'discount_percent',
        'is_sold_out',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SaleCampaign::class, 'sale_campaign_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->sale_quantity - $this->sold_quantity);
    }

    public function getDiscountPercentAttribute(): int
    {
        if ($this->original_price > 0 && $this->original_price > $this->sale_price) {
            return (int) round((($this->original_price - $this->sale_price) / $this->original_price) * 100);
        }

        return 0;
    }

    public function getIsSoldOutAttribute(): bool
    {
        return $this->sold_quantity >= $this->sale_quantity;
    }
}
