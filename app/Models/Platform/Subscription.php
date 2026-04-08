<?php

/**
 * Subscription.php
 *
 * PURPOSE: Platform-level subscription model.
 *          Har tenant ki active subscription track karta hai.
 *
 * TENANT-SCOPED: NO (Platform-level model)
 * TABLE: subscriptions
 */

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id',
        'cycle',
        'price',
        'original_price',
        'starts_at',
        'ends_at',
        'grace_ends_at',
        'razorpay_sub_id',
        'razorpay_plan_id',
        'status',
    ];

    protected $casts = [
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'grace_ends_at' => 'datetime',
        'price'         => 'decimal:2',
        'original_price' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
