<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Destination extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function primaryPackage(): HasOne
    {
        return $this->hasOne(Package::class)->where('is_active', true);
    }

    public function getBadgeLabelAttribute(): string
    {
        return match ($this->slug) {
            'greece-islands' => 'Island escape',
            'maldives-paradise' => 'Beach favorite',
            'canada-rockies' => 'Adventure',
            'japan-discovery' => 'Culture & nature',
            'bali-sanctuary' => 'Tropical culture',
            'swiss-alps' => 'Alpine luxury',
            default => $this->primaryPackage?->badge ?? 'Featured',
        };
    }

    public function getSearchKeywordsAttribute(): string
    {
        $pkg = $this->primaryPackage;
        $parts = [
            $this->name,
            $this->region,
            $pkg?->title,
            $pkg?->category,
            $this->badge_label,
        ];

        return strtolower(implode(' ', array_filter($parts)));
    }
}
