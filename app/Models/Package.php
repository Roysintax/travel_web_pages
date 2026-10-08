<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Package extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price_per_person' => 'decimal:2',
            'original_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'is_luxury' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** "Santorini & Mykonos, Greece" -> "Greece" (country label used on cards). */
    public function getCountryAttribute(): string
    {
        return trim(last(explode(',', $this->destination?->name ?? '')));
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format((float) $this->price_per_person, 0, ',', '.');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class)->orderBy('sort_order');
    }

    public function features(): HasMany
    {
        return $this->hasMany(PackageItem::class)->where('item_type', 'feature')->orderBy('sort_order');
    }

    public function included(): HasMany
    {
        return $this->hasMany(PackageItem::class)->where('item_type', 'included')->orderBy('sort_order');
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(PackageItinerary::class)->orderBy('day_number');
    }

    public function accommodation(): HasOne
    {
        return $this->hasOne(PackageAccommodation::class, 'package_id');
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->slug) {
            'greece-islands' => 'badge-orange',
            'maldives-paradise' => 'badge-emerald',
            'japan-discovery' => 'badge-rose',
            'canada-rockies' => 'badge-purple',
            'bali-sanctuary' => 'badge-emerald',
            'swiss-alps' => 'badge-blue',
            default => 'badge-blue',
        };
    }

    public function toCatalogArray(): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'destination' => $this->destination?->name ?? '',
            'region' => $this->destination?->region ?? '',
            'category' => $this->category,
            'badge' => $this->badge ?? '',
            'badgeClass' => $this->badge_class,
            'image' => $this->image?->file_path ?? 'assets/hero.png',
            'alt' => $this->image?->alt_text ?: $this->title,
            'days' => $this->days,
            'nights' => $this->nights,
            'price' => (int) $this->price_per_person,
            'originalPrice' => $this->original_price ? (int) $this->original_price : null,
            'rating' => $this->rating ? (float) $this->rating : 4.8,
            'reviews' => $this->review_count,
            'desc' => $this->description,
            'luxury' => (bool) $this->is_luxury,
            'features' => $this->features->pluck('description')->all(),
            'included' => $this->included->pluck('description')->all(),
            'itinerary' => $this->itineraries->map(fn ($it) => [
                'day' => 'Day '.$it->day_number,
                'title' => $it->title,
                'desc' => $it->description,
            ])->all(),
            'hotel' => [
                'name' => $this->accommodation?->name ?? 'Verified 4–5 Star Stay',
                'stars' => $this->accommodation?->star_label ?? '5-Star Luxury',
                'perks' => $this->accommodation?->perks ?? '',
            ],
        ];
    }
}
