<?php

namespace App\Actions\Pricing;

use App\Models\AirlineCommission;
use App\Models\PricingRule;

class ImportLegacyCommissions
{
    public function handle(): int
    {
        $imported = 0;

        AirlineCommission::query()
            ->orderBy('id')
            ->chunkById(100, function ($commissions) use (&$imported) {
                foreach ($commissions as $commission) {
                    $rule = PricingRule::updateOrCreate(
                        [
                            'carrier' => $commission->airline_code,
                            'priority' => 100,
                            'usage' => PricingRule::USAGE_COMMISSION_BASE,
                        ],
                        [
                            'origin' => null,
                            'destination' => null,
                            'both_ways' => false,
                            'travel_type' => 'OW+RT',
                            'cabin_class' => null,
                            'booking_class_rbd' => null,
                            'booking_class_usage' => PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE,
                            'passenger_types' => null,
                            'fare_type' => 'public_and_private',
                            'promo_code' => null,
                            'percent' => $this->nullableDecimal($commission->markup_percent),
                            'flat_amount' => $this->nullableDecimal($commission->flat_markup, 2),
                            'fee_percent' => null,
                            'fixed_fee' => null,
                            'active' => (bool) $commission->is_active,
                            'notes' => $commission->notes,
                            'calc_basis' => PricingRule::CALC_BASE_PRICE,
                        ]
                    );

                    if ($rule->wasRecentlyCreated || $rule->wasChanged()) {
                        $imported++;
                    }
                }
            });

        return $imported;
    }

    private function nullableDecimal(float|int|null $value, int $precision = 4): ?float
    {
        $numeric = is_numeric($value) ? (float) $value : null;

        if ($numeric === null) {
            return null;
        }

        $rounded = round($numeric, $precision);

        return $rounded === 0.0 ? null : $rounded;
    }
}
