<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'banner_url',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'status',
        'status_label',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleCampaignItem::class);
    }

    /**
     * Compute current status of the campaign
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'paused';
        }

        $now = now();
        if ($now->lt($this->start_time)) {
            return 'upcoming';
        }
        if ($now->gt($this->end_time)) {
            return 'ended';
        }

        return 'running';
    }

    /**
     * Human-readable status label in Vietnamese
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'running' => 'Đang diễn ra',
            'upcoming' => 'Sắp diễn ra',
            'ended' => 'Đã kết thúc',
            'paused' => 'Đã tạm dừng',
            default => 'Không xác định',
        };
    }

    /**
     * Check if campaign is currently live and active
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Scope for live running campaigns
     */
    public function scopeRunning(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now);
    }

    /**
     * Scope for upcoming campaigns
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('start_time', '>', $now);
    }

    /**
     * Scope for ended campaigns
     */
    public function scopeEnded(Builder $query): Builder
    {
        $now = now();

        return $query->where('end_time', '<', $now);
    }
}
