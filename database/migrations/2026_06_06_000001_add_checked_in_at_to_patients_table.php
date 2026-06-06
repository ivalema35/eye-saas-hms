<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Phone-app registered patients need physical check-in before
            // their wait timer starts. NULL = not yet arrived at hospital.
            $table->timestamp('checked_in_at')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('checked_in_at');
        });
    }
};
