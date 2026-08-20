<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable()->after('amount');
            $table->string('currency_symbol', 8)->nullable()->after('currency_code');
            $table->decimal('subtotal', 10, 2)->nullable()->after('currency_symbol');
            $table->decimal('gst_rate', 5, 2)->nullable()->after('subtotal');
            $table->decimal('gst_amount', 10, 2)->nullable()->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'currency_symbol',
                'subtotal',
                'gst_rate',
                'gst_amount',
            ]);
        });
    }
};
