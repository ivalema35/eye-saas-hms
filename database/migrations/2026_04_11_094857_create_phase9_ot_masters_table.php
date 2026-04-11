<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ot_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('ot_surgery_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('ot_type_id')->constrained('ot_types')->onDelete('cascade');
            $table->string('surgery_name');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'ot_type_id', 'surgery_name'], 'ot_surgery_types_unique_per_type');
        });

        Schema::create('ot_counselling', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('ot_booking_id')->constrained('ot_bookings')->onDelete('cascade');
            $table->boolean('mediclaim')->default(false);
            $table->string('lens_option')->nullable();
            $table->decimal('package_amount', 10, 2)->nullable();
            $table->string('payment_mode')->nullable();
            $table->boolean('report_ok')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('hospital_users');
            $table->timestamps();

            $table->index(['tenant_id', 'ot_booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ot_counselling');
        Schema::dropIfExists('ot_surgery_types');
        Schema::dropIfExists('ot_types');
    }
};
