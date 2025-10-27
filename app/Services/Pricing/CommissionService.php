<?php

namespace App\Services\Pricing;

use App\Models\AirlineCommission;

class CommissionService
{
    public function pricingForAirline(string $airlineCode, float $baseAmount): array
    {
        $airlineCode = strtoupper(trim($airlineCode));

        $commission = AirlineCommission::active()
            ->where('airline_code', $airlineCode)
            ->first();

        $percent = $commission?->markup_percent ?? config('pricing.defaults.markup_percent', 0);
        $flat = $commission?->flat_markup ?? config('pricing.defaults.flat_markup', 0);

        $percentageMarkup = round($baseAmount * ($percent / 100), 2);
        $markupTotal = round($percentageMarkup + $flat, 2);
        $displayTotal = round($baseAmount + $markupTotal, 2);

        return [
            'airline_code' => $airlineCode,
            'base_amount' => round($baseAmount, 2),
            'markup_amount' => $markupTotal,
            'percentage_component' => $percentageMarkup,
            'flat_component' => round($flat, 2),
            'percent_rate' => (float) $percent,
            'display_amount' => $displayTotal,
            'source' => $commission?->exists ? 'airline' : 'default',
        ];
    }
}
