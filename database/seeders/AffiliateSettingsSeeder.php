<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AffiliateSettingsSeeder extends Seeder
{
    /**
     * Seed the affiliate_settings table with default values.
     */
    public function run(): void
    {
        $defaults = [
            ['key' => 'commission_percent_default', 'value' => '5.00'],
            ['key' => 'cookie_days',                'value' => '30'],
            ['key' => 'grace_period_days',          'value' => '30'],
            ['key' => 'commission_trigger',         'value' => 'first_paid_order'],
        ];

        foreach ($defaults as $row) {
            DB::table('affiliate_settings')->updateOrInsert(
                ['key' => $row['key']],
                ['value' => $row['value'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
