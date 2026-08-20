<?php

/**
 * Payment.php
 *
 * PURPOSE: Platform-level payment transaction model.
 *          Saari payment transactions ka record rakhta hai.
 *
 * TENANT-SCOPED: NO (Platform-level model)
 * TABLE: payments
 */

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'amount',
        'currency_code',
        'currency_symbol',
        'subtotal',
        'gst_rate',
        'gst_amount',
        'cycle',
        'method',
        'gateway',
        'transaction_id',
        'razorpay_order_id',
        'razorpay_signature',
        'status',
        'paid_at',
        'invoice_path',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
