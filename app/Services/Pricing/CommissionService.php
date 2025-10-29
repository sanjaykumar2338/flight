<?php

namespace App\Services\Pricing;

use App\Models\AirlineCommission;

class CommissionService
{
    public function pricingForAirline(string $airlineCode, float $baseFare): array
    {
        $airlineCode = strtoupper(trim($airlineCode));

        $commission = AirlineCommission::active()
            ->where('airline_code', $airlineCode)
            ->first();

        $percent = $commission?->markup_percent ?? config('pricing.defaults.markup_percent', 0);

        $baseFare = round($baseFare, 2);
        $commissionAmount = round($baseFare * ($percent / 100), 2);
        $displayAmount = round($baseFare + $commissionAmount, 2);

        return [
            'airline_code' => $airlineCode,
            'base_amount' => $baseFare,
            'commission_amount' => $commissionAmount,
            'markup_amount' => $commissionAmount,
            'percentage_component' => $commissionAmount,
            'flat_component' => 0.0,
            'percent_rate' => (float) $percent,
            'display_amount' => $displayAmount,
            'source' => $commission?->exists ? 'airline' : 'default',
        ];
    }
}
