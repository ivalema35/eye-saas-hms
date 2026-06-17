<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterCity extends Model
{
    protected $table = 'tbl_master_cities';

    protected $fillable = ['state_id', 'district_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(MasterState::class, 'state_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(MasterDistrict::class, 'district_id');
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
