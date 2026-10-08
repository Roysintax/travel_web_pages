<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageAccommodation extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'package_id';

    public $incrementing = false;

    protected $guarded = [];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
