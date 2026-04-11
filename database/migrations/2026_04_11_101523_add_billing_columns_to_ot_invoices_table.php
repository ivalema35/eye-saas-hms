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
        Schema::table('ot_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('ot_invoices', 'invoice_number')) {
                $table->string('invoice_number')->unique()->after('ot_booking_id');
            }

            if (! Schema::hasColumn('ot_invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('total_amount');
            }

            if (! Schema::hasColumn('ot_invoices', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('ot_invoices', 'net_amount')) {
                $table->decimal('net_amount', 10, 2)->default(0)->after('discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ot_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('ot_invoices', 'invoice_number')) {
                $table->dropUnique('ot_invoices_invoice_number_unique');
                $table->dropColumn('invoice_number');
            }

            $columnsToDrop = [];

            if (Schema::hasColumn('ot_invoices', 'tax_amount')) {
                $columnsToDrop[] = 'tax_amount';
            }

            if (Schema::hasColumn('ot_invoices', 'discount')) {
                $columnsToDrop[] = 'discount';
            }

            if (Schema::hasColumn('ot_invoices', 'net_amount')) {
                $columnsToDrop[] = 'net_amount';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
