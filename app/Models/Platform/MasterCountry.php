<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterCountry extends Model
{
    protected $table = 'tbl_master_countries';

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

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
