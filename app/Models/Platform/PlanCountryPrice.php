<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-country plan price override.
 *
 * If a row exists for a country+cycle, this price is used on
 * the registration page instead of the INR fx-conversion fallback.
 */
class PlanCountryPrice extends Model
{
    protected $table = 'tbl_plan_country_prices';

    protected $fillable = [
        'country_id',
        'cycle',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(MasterCountry::class, 'country_id');
    }
}
