<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('dosage', 100)->nullable()->after('name');
            $table->string('duration', 100)->nullable()->after('dosage');
            $table->string('qty', 50)->nullable()->after('duration');
            $table->text('composition')->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn(['dosage', 'duration', 'qty', 'composition']);
        });
    }
};
