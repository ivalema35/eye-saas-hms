<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterCountry extends Model
{
    protected $table = 'tbl_master_countries';

    protected $fillable = [
        'name',
        'country_code',
        'default_timezone',
        'currency_code',
        'currency_symbol',
        'currency_name',
        'fx_inr_per_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fx_inr_per_unit' => 'float',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(MasterState::class, 'country_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function normalize(string $value): string
    {
        return ucwords(strtolower(trim($value)));
    }
}
