<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    public $timestamps = false;

    protected $table = 'page_sections';

    protected $fillable = [
        'page_id',
        'section_key',
        'heading',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
