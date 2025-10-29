<?php

namespace App\Services\Pricing;

use App\Models\PricingRule;
use App\Support\Money;

class PricingService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly CommissionService $commissionService
    ) {
    }

    /**
     * @param array<int, mixed> $itinerary
     * @param array<string, mixed> $passengers
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    public function calculate(array $itinerary, array $passengers, float $baseAmount, float $taxAmount, string $currency, array $ctx): array
    {
        $currency = strtoupper($currency ?: 'USD');
        $baseMoney = Money::fromDecimal($baseAmount, $currency);
        $ndcTotal = Money::fromDecimal($baseAmount + $taxAmount, $currency);

        $engineEnabled = (bool) config('pricing.rules.enabled', true);
        $context = $this->prepareContext($ctx, $currency, $taxAmount, $passengers);

        if ($engineEnabled) {
            $result = $this->pricingEngine->applyPricing(
                $itinerary,
                $passengers,
                $baseMoney,
                $ndcTotal,
                $context
            );

            if (!empty($result->rulesApplied())) {
                return $this->formatResult($result, $taxAmount, $passengers, $engineEnabled, true, null);
            }
        }

        $carrier = $context['carrier'] ?? '';
        $legacy = $this->commissionService->pricingForAirline($carrier, $baseAmount);
        $legacyResult = $this->buildLegacyResult($legacy, $baseMoney, $ndcTotal, $taxAmount, $currency, $context);

        return $this->formatResult($legacyResult, $taxAmount, $passengers, $engineEnabled, false, $legacy);
    }

    /**
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $passengers
     * @return array<string, mixed>
     */
    private function prepareContext(array $ctx, string $currency, float $taxAmount, array $passengers): array
    {
        $passengerTypes = collect($passengers['types'] ?? [])
            ->filter(fn ($count) => (int) $count > 0)
            ->keys()
            ->map(fn ($type) => strtoupper((string) $type))
            ->values()
            ->all();

        return array_merge($ctx, [
            'currency' => $currency,
            'tax_amount' => $taxAmount,
            'passenger_types' => $passengerTypes,
        ]);
    }

    /**
     * @param array<string, mixed>|null $legacy
     * @return array<string, mixed>
     */
    private function formatResult(PricingResult $result, float $taxAmount, array $passengers, bool $engineEnabled, bool $engineUsed, ?array $legacy): array
    {
        $base = $result->base()->toFloat();
        $totalAfter = $result->finalTotal()->toFloat();
        $adjustments = round($totalAfter - ($base + $taxAmount), 2);

        return [
            'ndc' => $result->ndc(),
            'rules_applied' => $result->rulesApplied(),
            'display_total' => $result->displayAmount()->toFloat(),
            'payable_total' => $result->payableTotal()->toFloat(),
            'components' => [
                'base_fare' => $base,
                'taxes' => $taxAmount,
                'adjustments' => $adjustments,
            ],
            'engine' => [
                'enabled' => $engineEnabled,
                'used' => $engineUsed,
                'result' => $engineUsed ? $result->toArray() : null,
            ],
            'legacy' => $legacy,
            'passengers' => $passengers,
            'context' => $result->context(),
            'breakdown' => $result->toArray(),
        ];
    }

    /**
     * @param array<string, mixed> $commission
     * @param array<string, mixed> $ctx
     */
    private function buildLegacyResult(array $commission, Money $base, Money $ndcTotal, float $taxAmount, string $currency, array $ctx): PricingResult
    {
        $impact = Money::fromDecimal($commission['commission_amount'] ?? 0.0, $currency);
        $finalTotal = $ndcTotal->add($impact);
        $sign = $impact->isNegative() ? '-' : '+';

        $rulesApplied = [[
            'id' => null,
            'priority' => 1000,
            'kind' => PricingRule::KIND_COMMISSION,
            'basis' => PricingRule::CALC_TOTAL_PRICE,
            'percent' => isset($commission['percent_rate']) ? (float) $commission['percent_rate'] : null,
            'flat_amount' => isset($commission['flat_component']) ? (float) $commission['flat_component'] : null,
            'fee_percent' => null,
            'fixed_fee' => null,
            'impact' => sprintf('%s%s', $sign, $impact->absolute()->formatted()),
            'impact_amount' => $impact->toFloat(),
            'applied_value' => $impact->absolute()->toFloat(),
            'label' => 'Legacy commission',
            'source' => $commission['source'] ?? 'legacy',
        ]];

        return new PricingResult(
            baseAmount: $base,
            startingTotal: $ndcTotal,
            finalTotal: $finalTotal,
            rulesApplied: $rulesApplied,
            ndcBreakdown: [
                'currency' => $ctx['currency'] ?? $currency,
                'base_amount' => $base->toFloat(),
                'tax_amount' => $taxAmount,
                'total_amount' => $ndcTotal->toFloat(),
            ],
            context: array_merge($ctx, [
                'legacy_source' => $commission['source'] ?? 'legacy',
            ])
        );
    }
}
