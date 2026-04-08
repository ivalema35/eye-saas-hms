<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secondary_examinations', function (Blueprint $table) {
            $table->text('advice')->nullable()->after('exam_data');
        });
    }

    public function down(): void
    {
        Schema::table('secondary_examinations', function (Blueprint $table) {
            $table->dropColumn('advice');
        });
    }
};
