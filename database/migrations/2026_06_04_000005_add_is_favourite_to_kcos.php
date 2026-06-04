<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kcos', function (Blueprint $table) {
            $table->boolean('is_favourite')->default(false)->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('kcos', function (Blueprint $table) {
            $table->dropColumn('is_favourite');
        });
    }
};
