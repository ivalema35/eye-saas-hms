<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->dropForeign('ot_lens_details_entered_by_foreign');
            $table->foreign('entered_by')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->dropForeign('ot_lens_details_entered_by_foreign');
            $table->foreign('entered_by')->references('id')->on('ot_staff');
        });
    }
};
