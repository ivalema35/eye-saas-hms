<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('primary_examinations', function (Blueprint $table) {
            $table->unsignedSmallInteger('dilation_time')->nullable()->after('exam_data');
        });
    }

    public function down(): void
    {
        Schema::table('primary_examinations', function (Blueprint $table) {
            $table->dropColumn('dilation_time');
        });
    }
};
