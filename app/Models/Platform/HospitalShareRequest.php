<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class HospitalShareRequest extends Model
{
    protected $table = 'hospital_share_requests';

    protected $fillable = ['from_tenant_id', 'to_tenant_id', 'status'];

    public function fromTenant()
    {
        return $this->belongsTo(Tenant::class, 'from_tenant_id');
    }

    public function toTenant()
    {
        return $this->belongsTo(Tenant::class, 'to_tenant_id');
    }
}
