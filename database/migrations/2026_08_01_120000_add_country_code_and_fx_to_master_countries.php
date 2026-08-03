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
            $table->string('country_code', 2)->nullable()->after('name');
            $table->decimal('fx_inr_per_unit', 12, 4)->default(1)->after('currency_name');
        });

        // ISO country code + approx INR per 1 local currency unit (for SaaS plan display)
        $map = [
            'india' => ['IN', 1],
            'australia' => ['AU', 55],
            'united states' => ['US', 83],
            'united states of america' => ['US', 83],
            'usa' => ['US', 83],
            'united kingdom' => ['GB', 105],
            'uk' => ['GB', 105],
            'united arab emirates' => ['AE', 22.5],
            'saudi arabia' => ['SA', 22.1],
            'singapore' => ['SG', 62],
            'canada' => ['CA', 60],
            'nepal' => ['NP', 0.63],
            'bangladesh' => ['BD', 0.76],
            'sri lanka' => ['LK', 0.27],
            'pakistan' => ['PK', 0.30],
            'qatar' => ['QA', 22.8],
            'kuwait' => ['KW', 270],
            'oman' => ['OM', 215],
            'bahrain' => ['BH', 220],
            'malaysia' => ['MY', 18.5],
            'kenya' => ['KE', 0.64],
            'nigeria' => ['NG', 0.055],
            'south africa' => ['ZA', 4.5],
            'germany' => ['DE', 90],
            'france' => ['FR', 90],
            'new zealand' => ['NZ', 50],
        ];

        foreach ($map as $name => [$code, $fx]) {
            DB::table('tbl_master_countries')
                ->whereRaw('LOWER(name) = ?', [$name])
                ->update([
                    'country_code' => $code,
                    'fx_inr_per_unit' => $fx,
                ]);
        }

        // Currency-based fallback when country name not in map
        $currencyFx = [
            'INR' => 1, 'AUD' => 55, 'USD' => 83, 'GBP' => 105, 'EUR' => 90,
            'AED' => 22.5, 'SAR' => 22.1, 'SGD' => 62, 'CAD' => 60, 'NPR' => 0.63,
            'BDT' => 0.76, 'LKR' => 0.27, 'PKR' => 0.30, 'QAR' => 22.8, 'KWD' => 270,
            'OMR' => 215, 'BHD' => 220, 'MYR' => 18.5, 'KES' => 0.64, 'NGN' => 0.055, 'ZAR' => 4.5,
        ];

        foreach ($currencyFx as $cur => $fx) {
            DB::table('tbl_master_countries')
                ->where('currency_code', $cur)
                ->where(function ($q) {
                    $q->whereNull('country_code')->orWhere('country_code', '');
                })
                ->update(['fx_inr_per_unit' => $fx]);
        }
    }

    public function down(): void
    {
        Schema::table('tbl_master_countries', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'fx_inr_per_unit']);
        });
    }
};
