<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingDropdownSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            'carriers' => [
                ['00', '00'],
                ['02', '02'],
                ['03', '03'],
                ['0B', '0B'],
                ['0C', '0C'],
                ['0D', '0D'],
            ],
            'travel_types' => [
                ['OW', 'One Way'],
                ['RT', 'Round Trip'],
                ['OW+RT', 'One Way & Round Trip'],
            ],
            'fare_types' => [
                ['public', 'Public'],
                ['private', 'Private'],
                ['public_and_private', 'Public and Private'],
            ],
            'cabin_classes' => [
                ['Economy', 'Economy'],
                ['Premium Economy', 'Premium Economy'],
                ['Business', 'Business'],
                ['First', 'First'],
                ['Premium First', 'Premium First'],
            ],
        ];

        foreach ($options as $type => $entries) {
            foreach ($entries as $index => [$value, $label]) {
                DB::table('pricing_dropdown_options')->updateOrInsert(
                    ['type' => $type, 'value' => $value],
                    [
                        'label' => $label,
                        'sort_order' => $index,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
