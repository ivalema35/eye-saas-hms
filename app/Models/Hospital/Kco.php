<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kco extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'kcos';

    protected $fillable = ['value', 'is_favourite'];

    protected $casts = ['is_favourite' => 'boolean'];
}
