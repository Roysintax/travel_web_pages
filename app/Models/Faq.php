<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
