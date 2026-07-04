<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registration form allows 3-4 letter hospital codes, but the column
        // was created as varchar(3), truncating any 4-letter code on insert.
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('hospital_code', 4)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('hospital_code', 3)->nullable(false)->change();
        });
    }
};
