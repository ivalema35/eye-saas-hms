<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_master_countries', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('INR')->after('default_timezone');
            $table->string('currency_symbol', 10)->default('₹')->after('currency_code');
            $table->string('currency_name', 50)->nullable()->after('currency_symbol');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('INR')->after('timezone');
            $table->string('currency_symbol', 10)->default('₹')->after('currency_code');
            $table->boolean('is_currency_override')->default(false)->after('currency_symbol');
        });

        // Sensible defaults for common country names already in master
        $defaults = [
            'india' => ['INR', '₹', 'Indian Rupee'],
            'united arab emirates' => ['AED', 'د.إ', 'UAE Dirham'],
            'united states' => ['USD', '$', 'US Dollar'],
            'united states of america' => ['USD', '$', 'US Dollar'],
            'usa' => ['USD', '$', 'US Dollar'],
            'united kingdom' => ['GBP', '£', 'British Pound'],
            'uk' => ['GBP', '£', 'British Pound'],
            'saudi arabia' => ['SAR', '﷼', 'Saudi Riyal'],
            'singapore' => ['SGD', 'S$', 'Singapore Dollar'],
            'australia' => ['AUD', 'A$', 'Australian Dollar'],
            'canada' => ['CAD', 'C$', 'Canadian Dollar'],
            'nepal' => ['NPR', 'रू', 'Nepalese Rupee'],
            'bangladesh' => ['BDT', '৳', 'Bangladeshi Taka'],
            'sri lanka' => ['LKR', 'Rs', 'Sri Lankan Rupee'],
            'pakistan' => ['PKR', 'Rs', 'Pakistani Rupee'],
            'qatar' => ['QAR', 'ر.ق', 'Qatari Riyal'],
            'kuwait' => ['KWD', 'د.ك', 'Kuwaiti Dinar'],
            'oman' => ['OMR', 'ر.ع.', 'Omani Rial'],
            'bahrain' => ['BHD', '.د.ب', 'Bahraini Dinar'],
            'malaysia' => ['MYR', 'RM', 'Malaysian Ringgit'],
            'kenya' => ['KES', 'KSh', 'Kenyan Shilling'],
            'nigeria' => ['NGN', '₦', 'Nigerian Naira'],
            'south africa' => ['ZAR', 'R', 'South African Rand'],
        ];

        foreach ($defaults as $name => [$code, $symbol, $currencyName]) {
            DB::table('tbl_master_countries')
                ->whereRaw('LOWER(name) = ?', [$name])
                ->update([
                    'currency_code' => $code,
                    'currency_symbol' => $symbol,
                    'currency_name' => $currencyName,
                ]);
        }

        // Sync existing tenants from their country master row
        $countries = DB::table('tbl_master_countries')->get(['name', 'currency_code', 'currency_symbol']);
        foreach ($countries as $country) {
            DB::table('tenants')
                ->whereRaw('LOWER(country) = ?', [strtolower($country->name)])
                ->where('is_currency_override', false)
                ->update([
                    'currency_code' => $country->currency_code,
                    'currency_symbol' => $country->currency_symbol,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol', 'is_currency_override']);
        });

        Schema::table('tbl_master_countries', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol', 'currency_name']);
        });
    }
};
