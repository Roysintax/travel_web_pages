<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'page_media', 'page_id', 'media_id')->withPivot('placement', 'sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }
}
