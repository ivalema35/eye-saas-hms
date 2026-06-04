<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'tbl_master_sac',
        'tbl_master_lid',
        'tbl_master_conj',
        'tbl_master_cornea',
        'tbl_master_ac',
        'tbl_master_iris',
        'tbl_master_pupil',
        'tbl_master_lens',
        'tbl_master_em',
        'tbl_master_covertest',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('is_favourite')->default(false)->after('value');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('is_favourite');
            });
        }
    }
};
