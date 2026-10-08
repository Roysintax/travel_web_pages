<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'travelers' => 'integer',
            'unit_price_snapshot' => 'decimal:2',
            'estimated_total' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public static function generateReferenceCode(): string
    {
        return 'TRV-'.now()->year.'-'.Str::upper(Str::random(24));
    }
}
