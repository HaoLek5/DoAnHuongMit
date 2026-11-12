<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_name',
        'receiver_phone',
        'province',
        'district',
        'ward',
        'detail_address',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $appends = [
        'full_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Địa chỉ hoàn chỉnh
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->detail_address,
            $this->ward,
            $this->district,
            $this->province,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Tự động xử lý khi đặt địa chỉ mặc định
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($address) {
            if ($address->is_default) {
                // Đặt tất cả địa chỉ khác của người dùng này về không mặc định
                static::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }
}
