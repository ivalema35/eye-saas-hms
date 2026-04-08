<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_admins', function (Blueprint $table) {
            // role_id FK — nullable because existing records don't have it yet
            $table->foreignId('role_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('roles')
                ->onDelete('set null');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('hospital_admins', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropSoftDeletes();
        });
    }
};
