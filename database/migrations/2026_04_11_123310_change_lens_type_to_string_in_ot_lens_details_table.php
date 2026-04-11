<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->string('lens_type', 150)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->enum('lens_type', ['A', 'B'])->change();
        });
    }
};
