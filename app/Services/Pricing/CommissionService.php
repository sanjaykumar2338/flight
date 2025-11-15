<?php

namespace App\Services\Pricing;

use App\Models\AirlineCommission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    public function pricingForAirline(string $airlineCode, float $baseFare): array
    {
        $airlineCode = strtoupper(trim($airlineCode));
        $commission = null;

        try {
            $commission = AirlineCommission::active()
                ->where('airline_code', $airlineCode)
                ->first();
        } catch (QueryException $exception) {
            Log::warning('Unable to load airline commission; using defaults.', [
                'airline_code' => $airlineCode,
                'message' => $exception->getMessage(),
            ]);
        }

        $percent = $commission?->markup_percent ?? config('pricing.defaults.markup_percent', 0);
        $flat = $commission?->flat_markup ?? config('pricing.defaults.flat_markup', 0);
        $baseFare = round($baseFare, 2);
        $percentComponent = round($baseFare * ($percent / 100), 2);
        $commissionAmount = round($percentComponent + $flat, 2);
        $displayAmount = round($baseFare + $commissionAmount, 2);

        return [
            'airline_code' => $airlineCode,
            'base_amount' => $baseFare,
            'commission_amount' => $commissionAmount,
            'markup_amount' => $commissionAmount,
            'percentage_component' => $percentComponent,
            'flat_component' => $flat,
            'percent_rate' => (float) $percent,
            'display_amount' => $displayAmount,
            'source' => $commission?->exists ? 'airline' : 'default',
        ];
    }
}
