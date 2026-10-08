<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
    ];

    /**
     * Get all settings as key-value array.
     *
     * @return array<string, string>
     */
    public static function allKeyValues(): array
    {
        return Cache::remember('site_settings_all', 300, function () {
            return static::pluck('setting_value', 'setting_key')->toArray();
        });
    }
}
