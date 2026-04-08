<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCovertest extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_covertest';

    protected $fillable = ['value'];
}

if (! class_exists(__NAMESPACE__.'\\MasterCoverTest', false)) {
    class_alias(MasterCovertest::class, __NAMESPACE__.'\\MasterCoverTest');
}
