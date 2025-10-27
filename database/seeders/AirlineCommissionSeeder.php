<?php

namespace Database\Seeders;

use App\Models\AirlineCommission;
use Illuminate\Database\Seeder;

class AirlineCommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commissions = [
            [
                'airline_code' => 'SQ',
                'airline_name' => 'Singapore Airlines',
                'markup_percent' => 7.5,
                'flat_markup' => 25,
            ],
            [
                'airline_code' => 'EK',
                'airline_name' => 'Emirates',
                'markup_percent' => 6.0,
                'flat_markup' => 20,
            ],
            [
                'airline_code' => 'BA',
                'airline_name' => 'British Airways',
                'markup_percent' => 5.0,
                'flat_markup' => 15,
            ],
        ];

        foreach ($commissions as $commission) {
            AirlineCommission::updateOrCreate(
                ['airline_code' => $commission['airline_code']],
                [
                    'airline_name' => $commission['airline_name'],
                    'markup_percent' => $commission['markup_percent'],
                    'flat_markup' => $commission['flat_markup'],
                    'is_active' => true,
                ]
            );
        }
    }
}
