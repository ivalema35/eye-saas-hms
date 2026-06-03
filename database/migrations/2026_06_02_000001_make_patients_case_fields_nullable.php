<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('case_id')->nullable()->change();
            $table->decimal('case_fee', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('case_id')->nullable(false)->change();
            $table->decimal('case_fee', 10, 2)->nullable(false)->change();
        });
    }
};
