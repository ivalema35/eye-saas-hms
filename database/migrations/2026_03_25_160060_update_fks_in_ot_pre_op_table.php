<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_pre_op', function (Blueprint $table) {
            $table->dropForeign('ot_pre_op_entered_by_foreign');
            $table->foreign('entered_by')->references('id')->on('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ot_pre_op', function (Blueprint $table) {
            $table->dropForeign('ot_pre_op_entered_by_foreign');
            $table->foreign('entered_by')->references('id')->on('ot_staff');
        });
    }
};
