<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_prescriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_prescriptions', 'quantity')) {
                $table->unsignedSmallInteger('quantity')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('exam_prescriptions', 'route_id')) {
                $table->foreignId('route_id')
                    ->nullable()
                    ->after('quantity')
                    ->constrained('medicine_routes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_prescriptions', 'route_id')) {
                $table->dropConstrainedForeignId('route_id');
            }
            if (Schema::hasColumn('exam_prescriptions', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
