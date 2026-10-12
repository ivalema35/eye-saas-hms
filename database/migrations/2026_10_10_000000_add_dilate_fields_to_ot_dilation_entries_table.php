<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_dilation_entries', function (Blueprint $table) {
            $table->enum('dilate', ['Yes', 'No'])->nullable()->after('remarks');
            $table->unsignedSmallInteger('dilation_time')->nullable()->after('dilate');
        });
    }

    public function down(): void
    {
        Schema::table('ot_dilation_entries', function (Blueprint $table) {
            $table->dropColumn(['dilate', 'dilation_time']);
        });
    }
};
