<?php

namespace Database\Seeders;

use App\Models\AirlineCommission;
use Illuminate\Database\Seeder;

class PricingRuleImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!AirlineCommission::query()->exists()) {
            return;
        }

        app(\App\Actions\Pricing\ImportLegacyCommissions::class)->handle();
    }
}
