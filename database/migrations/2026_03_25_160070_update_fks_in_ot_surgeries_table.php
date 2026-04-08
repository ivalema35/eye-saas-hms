<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_surgeries', function (Blueprint $table) {
            $table->dropForeign('ot_surgeries_operated_by_foreign');
            $table->foreign('operated_by')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        Schema::table('ot_surgeries', function (Blueprint $table) {
            $table->dropForeign('ot_surgeries_operated_by_foreign');
            $table->foreign('operated_by')->references('id')->on('ot_staff');
        });
    }
};
