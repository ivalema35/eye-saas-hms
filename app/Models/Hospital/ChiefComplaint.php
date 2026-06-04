<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChiefComplaint extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'chief_complaints';

    protected $fillable = ['value', 'is_favourite'];

    protected $casts = ['is_favourite' => 'boolean'];
}
