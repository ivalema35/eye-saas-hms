<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_locations';

    protected $fillable = ['city', 'district', 'state'];

    public function getNameAttribute(): string
    {
        return trim(implode(', ', array_filter([$this->city, $this->district, $this->state])));
    }
}
