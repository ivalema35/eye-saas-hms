<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAxis extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_axis';

    protected $fillable = ['value'];

    protected function value(): Attribute
    {
        return Attribute::make(
            set: function (string $value): string {
                $trimmed = trim($value);

                return str_contains($trimmed, '°') ? $trimmed : $trimmed.'°';
            },
        );
    }
}
