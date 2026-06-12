<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospital_share_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_tenant_id');
            $table->unsignedBigInteger('to_tenant_id');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('from_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('to_tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['from_tenant_id', 'to_tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_share_requests');
    }
};
