<?php

namespace App\Services\Pricing;

use App\Models\PricingRule;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingEngine
{
    private int $cacheTtlSeconds;

    public function __construct(int $cacheTtlSeconds = 300)
    {
        $this->cacheTtlSeconds = (int) config('pricing.rules.cache_ttl', $cacheTtlSeconds);
    }

    /**
     * @param array<int, mixed> $itinerary
     * @param array<string, mixed> $paxBreakdown
     * @param array<string, mixed> $ctx
     */
    public function applyPricing(array $itinerary, array $paxBreakdown, Money $base, Money $total, array $ctx): PricingResult
    {
        $carrier = $this->normalizeCode(Arr::get($ctx, 'carrier'));
        $platingCarrier = $this->normalizeCode(Arr::get($ctx, 'plating_carrier'));
        $marketingCarriers = $this->normalizeCodes(Arr::get($ctx, 'marketing_carriers'));
        $operatingCarriers = $this->normalizeCodes(Arr::get($ctx, 'operating_carriers'));
        $flightNumbers = $this->normalizeCodes(Arr::get($ctx, 'flight_numbers'));
        $flightNumbers = empty($flightNumbers)
            ? $this->extractFlightNumbersFromItinerary($itinerary)
            : $flightNumbers;
        $currency = $total->currency();

        $rules = $this->activeRulesForCarrier($carrier);
        $passengerTypes = $this->resolvePassengerTypes($ctx, $paxBreakdown);
        $departureDate = $this->parseDate(Arr::get($ctx, 'departure_date'));
        $returnDate = $this->parseDate(Arr::get($ctx, 'return_date'));
        $salesDate = $this->parseDate(Arr::get($ctx, 'sales_date')) ?? Carbon::now();
        $travelType = $this->normalizeTravelType(Arr::get($ctx, 'travel_type'));
        $cabinClass = $this->normalizeCabinClass(Arr::get($ctx, 'cabin_class'));
        $fareType = $this->normalizeFareType(Arr::get($ctx, 'fare_type'));
        $promoCode = $this->normalizeString(Arr::get($ctx, 'promo_code'));
        $origin = $this->normalizeIata(Arr::get($ctx, 'origin'));
        $destination = $this->normalizeIata(Arr::get($ctx, 'destination'));
        $bookingClass = $this->normalizeString(Arr::get($ctx, 'booking_class_rbd', Arr::get($ctx, 'rbd')));

        $candidates = $rules->filter(function (PricingRule $rule) use (
            $origin,
            $destination,
            $travelType,
            $cabinClass,
            $fareType,
            $promoCode,
            $bookingClass,
            $passengerTypes,
            $salesDate,
            $departureDate,
            $returnDate,
            $platingCarrier,
            $marketingCarriers,
            $operatingCarriers,
            $flightNumbers
        ) {
            return $this->ruleMatches(
                $rule,
                $origin,
                $destination,
                $travelType,
                $cabinClass,
                $fareType,
                $promoCode,
                $bookingClass,
                $passengerTypes,
                $salesDate,
                $departureDate,
                $returnDate,
                $platingCarrier,
                $marketingCarriers,
                $operatingCarriers,
                $flightNumbers
            );
        });

        $ordered = $candidates->sort(function (PricingRule $a, PricingRule $b) {
            if ($a->priority === $b->priority) {
                $specificityA = $this->specificityScore($a);
                $specificityB = $this->specificityScore($b);

                if ($specificityA === $specificityB) {
                    return $a->id <=> $b->id;
                }

                return $specificityB <=> $specificityA;
            }

            return $a->priority <=> $b->priority;
        })->values();

        $runningTotal = $total;
        $appliedRules = [];

        foreach ($ordered as $rule) {
            $application = $this->applyRule($rule, $base, $runningTotal, $currency, [
                'promo_code' => $promoCode,
            ]);

            if ($application === null) {
                continue;
            }

            $runningTotal = $application['new_total'];
            $appliedRules[] = $this->formatAppliedRule(
                $rule,
                $application['difference'],
                $application['absolute'],
                $application['components']
            );
        }

        $ndc = $this->buildNdcBreakdown($ctx, $base, $total);

        return new PricingResult(
            baseAmount: $base,
            startingTotal: $total,
            finalTotal: $runningTotal,
            rulesApplied: $appliedRules,
            ndcBreakdown: $ndc,
            context: [
                'carrier' => $carrier,
                'origin' => $origin,
                'destination' => $destination,
                'travel_type' => $travelType,
                'cabin_class' => $cabinClass,
                'fare_type' => $fareType,
                'promo_code' => $promoCode,
                'booking_class' => $bookingClass,
            ]
        );
    }

    protected function activeRulesForCarrier(?string $carrier): Collection
    {
        try {
            $generic = Cache::remember(
                PricingRule::CACHE_KEY_PREFIX.PricingRule::CACHE_KEY_GENERIC,
                $this->cacheTtlSeconds,
                fn () => PricingRule::query()
                    ->active()
                    ->whereNull('carrier')
                    ->orderBy('priority')
                    ->orderBy('id')
                    ->get()
            );
        } catch (QueryException $exception) {
            Log::warning('Unable to load generic pricing rules; falling back to defaults.', [
                'message' => $exception->getMessage(),
            ]);

            return collect();
        }

        if (!$carrier) {
            return $generic;
        }

        try {
            $specific = Cache::remember(
                PricingRule::CACHE_KEY_PREFIX.$carrier,
                $this->cacheTtlSeconds,
                fn () => PricingRule::query()
                    ->active()
                    ->where('carrier', $carrier)
                    ->orderBy('priority')
                    ->orderBy('id')
                    ->get()
            );
        } catch (QueryException $exception) {
            Log::warning('Unable to load carrier-specific pricing rules; using generic set.', [
                'carrier' => $carrier,
                'message' => $exception->getMessage(),
            ]);

            return $generic;
        }

        return $generic->merge($specific);
    }

    /**
     * @param array<int, string> $passengerTypes
     */
    protected function ruleMatches(
        PricingRule $rule,
        ?string $origin,
        ?string $destination,
        ?string $travelType,
        ?string $cabinClass,
        ?string $fareType,
        ?string $promoCode,
        ?string $bookingClass,
        array $passengerTypes,
        Carbon $salesDate,
        ?Carbon $departureDate,
        ?Carbon $returnDate,
        ?string $platingCarrier,
        array $marketingCarriers,
        array $operatingCarriers,
        array $flightNumbers
    ): bool {
        if (!$this->matchesPlatingCarrier($rule, $platingCarrier)) {
            return false;
        }

        if (!$this->matchesCarrierRule(
            $rule->marketing_carriers_rule,
            $rule->marketing_carriers ?? [],
            $marketingCarriers,
            $operatingCarriers,
            $platingCarrier
        )) {
            return false;
        }

        if (!$this->matchesCarrierRule(
            $rule->operating_carriers_rule,
            $rule->operating_carriers ?? [],
            $operatingCarriers,
            $marketingCarriers,
            $platingCarrier
        )) {
            return false;
        }

        if (!$this->matchesAirports($rule, $origin, $destination)) {
            return false;
        }

        if (!$this->matchesTravelType($rule, $travelType)) {
            return false;
        }

        if (!$this->matchesCabin($rule, $cabinClass)) {
            return false;
        }

        if (!$this->matchesFareType($rule, $fareType)) {
            return false;
        }

        if (!$this->matchesPromoCode($rule, $promoCode)) {
            return false;
        }

        if (!$this->matchesBookingClass($rule, $bookingClass)) {
            return false;
        }

        if (!$this->matchesPassengerTypes($rule, $passengerTypes)) {
            return false;
        }

        if (!$this->matchesSalesWindow($rule, $salesDate)) {
            return false;
        }

        if (!$this->matchesDepartureWindow($rule, $departureDate)) {
            return false;
        }

        if (!$this->matchesReturnWindow($rule, $returnDate)) {
            return false;
        }

        if (!$this->matchesFlightRestriction($rule, $flightNumbers)) {
            return false;
        }

        return true;
    }

    private function matchesAirports(PricingRule $rule, ?string $origin, ?string $destination): bool
    {
        $ruleOrigin = $this->normalizeIata($rule->origin);
        $ruleDestination = $this->normalizeIata($rule->destination);

        if (!$ruleOrigin && !$ruleDestination) {
            return true;
        }

        $directMatch = (!$ruleOrigin || $ruleOrigin === $origin)
            && (!$ruleDestination || $ruleDestination === $destination);

        if ($directMatch) {
            return true;
        }

        if (!$rule->both_ways) {
            return false;
        }

        return (!$ruleOrigin || $ruleOrigin === $destination)
            && (!$ruleDestination || $ruleDestination === $origin);
    }

    private function matchesPlatingCarrier(PricingRule $rule, ?string $platingCarrier): bool
    {
        if (!$rule->plating_carrier) {
            return true;
        }

        return $this->normalizeCode($rule->plating_carrier) === $this->normalizeCode($platingCarrier);
    }

    /**
     * @param array<int, string> $ruleList
     * @param array<int, string> $contextList
     * @param array<int, string> $otherCarriers
     */
    private function matchesCarrierRule(
        ?string $ruleType,
        array $ruleList,
        array $contextList,
        array $otherCarriers = [],
        ?string $platingCarrier = null
    ): bool {
        $ruleType = PricingRule::normalizeOperatingRule($ruleType)
            ?? PricingRule::normalizeMarketingRule($ruleType)
            ?? PricingRule::AIRLINE_RULE_NO_RESTRICTION;
        $ruleCodes = $this->normalizeCodes($ruleList);
        $contextCodes = $this->normalizeCodes($contextList);
        $plating = $this->normalizeCode($platingCarrier);

        if ($ruleType === PricingRule::AIRLINE_RULE_NO_RESTRICTION) {
            return true;
        }

        if (empty($contextCodes)) {
            // If we require a restriction but have no context, treat as not matched.
            return false;
        }

        $intersection = array_intersect($ruleCodes, $contextCodes);

        return match ($ruleType) {
            PricingRule::AIRLINE_RULE_ONLY_LISTED => !empty($intersection),
            PricingRule::AIRLINE_RULE_EXCLUDE_LISTED => empty($intersection),
            PricingRule::AIRLINE_RULE_DIFFERENT_MARKETING => count($contextCodes) > 1,
            PricingRule::AIRLINE_RULE_PLATING_ONLY => $plating !== null && !empty($contextCodes) && count(array_unique(array_merge($contextCodes, [$plating]))) === 1,
            PricingRule::AIRLINE_RULE_OTHER_THAN_PLATING => $plating !== null && !empty($contextCodes) && !in_array($plating, $contextCodes, true),
            PricingRule::AIRLINE_RULE_INCLUDE_ALL => !empty($ruleCodes) && empty(array_diff($ruleCodes, $contextCodes)),
            default => true,
        };
    }

    /**
     * @param array<int, string> $flightNumbers
     */
    private function matchesFlightRestriction(PricingRule $rule, array $flightNumbers): bool
    {
        $restriction = $rule->flight_restriction_type ?: PricingRule::FLIGHT_RESTRICTION_NONE;
        $ruleNumbers = $this->normalizeCodes(
            $rule->flight_numbers ? explode(',', (string) $rule->flight_numbers) : []
        );

        if ($restriction === PricingRule::FLIGHT_RESTRICTION_NONE) {
            return true;
        }

        if (empty($ruleNumbers)) {
            // If a restriction is requested but no numbers are provided, fail safe.
            return false;
        }

        $contextNumbers = $this->normalizeCodes($flightNumbers);
        if (empty($contextNumbers)) {
            return false;
        }

        $intersection = array_intersect($ruleNumbers, $contextNumbers);

        return match ($restriction) {
            PricingRule::FLIGHT_RESTRICTION_ONLY_LISTED => !empty($intersection),
            PricingRule::FLIGHT_RESTRICTION_EXCLUDE_LISTED => empty($intersection),
            default => true,
        };
    }

    private function matchesTravelType(PricingRule $rule, ?string $travelType): bool
    {
        $ruleType = $this->normalizeTravelType($rule->travel_type);

        if ($ruleType === null || $ruleType === 'OW+RT') {
            return true;
        }

        if (!$travelType) {
            return false;
        }

        if ($travelType === 'OW+RT') {
            return true;
        }

        return $ruleType === $travelType;
    }

    private function matchesCabin(PricingRule $rule, ?string $cabinClass): bool
    {
        if (!$rule->cabin_class) {
            return true;
        }

        if (!$cabinClass) {
            return false;
        }

        return strcasecmp($rule->cabin_class, $cabinClass) === 0;
    }

    private function matchesFareType(PricingRule $rule, ?string $fareType): bool
    {
        $ruleFare = $rule->fare_type ? strtolower($rule->fare_type) : null;

        if ($ruleFare === null || $ruleFare === 'public_and_private') {
            return true;
        }

        if (!$fareType) {
            return false;
        }

        if ($fareType === 'public_and_private') {
            return true;
        }

        return $ruleFare === strtolower($fareType);
    }

    private function matchesPromoCode(PricingRule $rule, ?string $promoCode): bool
    {
        if (!$rule->promo_code) {
            return $rule->usage !== PricingRule::USAGE_DISCOUNT_TOTAL_PROMO;
        }

        return $promoCode && strtoupper($rule->promo_code) === $promoCode;
    }

    private function matchesBookingClass(PricingRule $rule, ?string $bookingClass): bool
    {
        $codes = $this->parseBookingClasses($rule->booking_class_rbd);

        if (empty($codes)) {
            return true;
        }

        $usage = $rule->booking_class_usage ?? PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE;

        if (!$bookingClass) {
            return $usage === PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED;
        }

        $bookingClass = strtoupper($bookingClass);

        return match ($usage) {
            PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED,
            PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE => in_array($bookingClass, $codes, true),
            PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED => !in_array($bookingClass, $codes, true),
            default => true,
        };
    }

    /**
     * @param array<int, string> $passengerTypes
     */
    private function matchesPassengerTypes(PricingRule $rule, array $passengerTypes): bool
    {
        $ruleTypes = collect($rule->passenger_types ?? [])
            ->filter()
            ->map(fn ($type) => strtoupper((string) $type))
            ->values();

        if ($ruleTypes->isEmpty()) {
            return true;
        }

        if (empty($passengerTypes)) {
            return false;
        }

        return collect($passengerTypes)->intersect($ruleTypes)->isNotEmpty();
    }

    private function matchesSalesWindow(PricingRule $rule, Carbon $salesDate): bool
    {
        if ($rule->sales_since && $salesDate->lt($rule->sales_since)) {
            return false;
        }

        if ($rule->sales_till && $salesDate->gt($rule->sales_till)) {
            return false;
        }

        return true;
    }

    private function matchesDepartureWindow(PricingRule $rule, ?Carbon $departureDate): bool
    {
        if (!$rule->departures_since && !$rule->departures_till) {
            return true;
        }

        if (!$departureDate) {
            return false;
        }

        if ($rule->departures_since && $departureDate->lt($rule->departures_since)) {
            return false;
        }

        if ($rule->departures_till && $departureDate->gt($rule->departures_till)) {
            return false;
        }

        return true;
    }

    private function matchesReturnWindow(PricingRule $rule, ?Carbon $returnDate): bool
    {
        if (!$rule->returns_since && !$rule->returns_till) {
            return true;
        }

        if (!$returnDate) {
            return false;
        }

        if ($rule->returns_since && $returnDate->lt($rule->returns_since)) {
            return false;
        }

        if ($rule->returns_till && $returnDate->gt($rule->returns_till)) {
            return false;
        }

        return true;
    }

    private function applyRule(PricingRule $rule, Money $base, Money $currentTotal, string $currency, array $context): ?array
    {
        $usage = $rule->usage ?? PricingRule::USAGE_COMMISSION_BASE;
        $components = [];
        $newTotal = $currentTotal;

        $baseCommission = $this->moneyFromPercent($rule->percent, $base)
            ->add($this->moneyFromFlat($rule->flat_amount, $currency));
        $baseAdjustments = $this->moneyFromPercent($rule->fee_percent, $base)
            ->add($this->moneyFromFlat($rule->fixed_fee, $currency));

        $totalAdjustments = $this->moneyFromPercent($rule->percent, $currentTotal)
            ->add($this->moneyFromFlat($rule->flat_amount, $currency))
            ->add($this->moneyFromPercent($rule->fee_percent, $currentTotal))
            ->add($this->moneyFromFlat($rule->fixed_fee, $currency));

        switch ($usage) {
            case PricingRule::USAGE_COMMISSION_BASE:
                $commission = $baseCommission->add($baseAdjustments);

                if ($commission->isZero()) {
                    return null;
                }

                $newTotal = $currentTotal->add($commission);
                $components['commission'] = $commission->toFloat();
                break;

            case PricingRule::USAGE_DISCOUNT_BASE:
                $discount = $baseCommission->add($baseAdjustments);

                if ($discount->isZero()) {
                    return null;
                }

                $newTotal = $currentTotal->subtract($discount)->clampMin(Money::zero($currency));
                $components['discount'] = -$discount->toFloat();
                break;

            case PricingRule::USAGE_DISCOUNT_TOTAL_PROMO:
                if ($totalAdjustments->isZero()) {
                    return null;
                }

                $newTotal = $currentTotal->subtract($totalAdjustments)->clampMin(Money::zero($currency));
                $components['promo_discount'] = -$totalAdjustments->toFloat();
                break;

            case PricingRule::USAGE_COMMISSION_DISCOUNT_BASE:
                $commission = $baseCommission;
                $discountComponent = $baseAdjustments;

                if ($commission->isZero() && $discountComponent->isZero()) {
                    return null;
                }

                if (!$commission->isZero()) {
                    $newTotal = $newTotal->add($commission);
                    $components['commission'] = $commission->toFloat();
                }

                if (!$discountComponent->isZero()) {
                    $newTotal = $newTotal->subtract($discountComponent)->clampMin(Money::zero($currency));
                    $components['discount'] = -$discountComponent->toFloat();
                }

                break;

            default:
                return null;
        }

        $difference = $newTotal->subtract($currentTotal);

        if ($difference->isZero()) {
            return null;
        }

        return [
            'new_total' => $newTotal,
            'difference' => $difference,
            'absolute' => $difference->absolute(),
            'components' => $components,
        ];
    }

    private function formatAppliedRule(PricingRule $rule, Money $difference, Money $absolute, array $components = []): array
    {
        $sign = $difference->isNegative() ? '-' : '+';

        return [
            'id' => $rule->id,
            'priority' => $rule->priority,
            'usage' => $rule->usage,
            'kind' => $rule->kind,
            'basis' => $rule->calc_basis,
            'percent' => $rule->percent !== null ? (float) $rule->percent : null,
            'flat_amount' => $rule->flat_amount !== null ? (float) $rule->flat_amount : null,
            'fee_percent' => $rule->fee_percent !== null ? (float) $rule->fee_percent : null,
            'fixed_fee' => $rule->fixed_fee !== null ? (float) $rule->fixed_fee : null,
            'impact' => sprintf('%s%s', $sign, $absolute->formatted()),
            'impact_amount' => $difference->toFloat(),
            'applied_value' => $absolute->toFloat(),
            'components' => $components,
        ];
    }

    private function specificityScore(PricingRule $rule): int
    {
        $score = 0;

        foreach (['carrier', 'origin', 'destination', 'promo_code', 'booking_class_rbd'] as $field) {
            if (!empty($rule->{$field})) {
                $score++;
            }
        }

        if ($rule->travel_type && $rule->travel_type !== 'OW+RT') {
            $score++;
        }

        if ($rule->cabin_class) {
            $score++;
        }

        if ($rule->fare_type && $rule->fare_type !== 'public_and_private') {
            $score++;
        }

        if ($rule->booking_class_usage) {
            $score++;
        }

        if (!empty($rule->passenger_types)) {
            $score += count($rule->passenger_types);
        }

        foreach ([
            'sales_since',
            'sales_till',
            'departures_since',
            'departures_till',
            'returns_since',
            'returns_till',
        ] as $field) {
            if (!empty($rule->{$field})) {
                $score++;
            }
        }

        if ($rule->both_ways) {
            $score++;
        }

        return $score;
    }

    /**
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $paxBreakdown
     * @return array<int, string>
     */
    private function resolvePassengerTypes(array $ctx, array $paxBreakdown): array
    {
        $types = collect(Arr::get($ctx, 'passenger_types', []))
            ->merge(Arr::get($paxBreakdown, 'types', []))
            ->merge($this->extractPassengerTypesFromBreakdown($paxBreakdown))
            ->filter()
            ->map(fn ($type) => strtoupper((string) $type))
            ->unique()
            ->values();

        return $types->all();
    }

    private function moneyFromPercent(float|int|string|null $percent, Money $basis): Money
    {
        if ($percent === null) {
            return Money::zero($basis->currency());
        }

        return $basis->percentage((float) $percent);
    }

    private function moneyFromFlat(float|int|string|null $amount, string $currency): Money
    {
        if ($amount === null) {
            return Money::zero($currency);
        }

        return Money::fromDecimal((float) $amount, $currency);
    }

    /**
     * @param array<string, mixed> $paxBreakdown
     * @return array<int, string>
     */
    private function extractPassengerTypesFromBreakdown(array $paxBreakdown): array
    {
        return collect($paxBreakdown)
            ->filter(fn ($value, $key) => is_numeric($value) && $value > 0 && is_string($key))
            ->keys()
            ->map(fn ($key) => strtoupper($key))
            ->all();
    }

    private function parseDate(null|string|Carbon $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if (!$value) {
            return null;
        }

        return Carbon::parse($value);
    }

    private function normalizeTravelType(?string $travelType): ?string
    {
        return match (strtoupper((string) $travelType)) {
            'OW', 'ONEWAY', 'ONE-WAY' => 'OW',
            'RT', 'ROUNDTRIP', 'ROUND-TRIP', 'RETURN' => 'RT',
            'OW+RT', 'BOTH', 'ALL' => 'OW+RT',
            default => null,
        };
    }

    private function normalizeCabinClass(?string $cabin): ?string
    {
        return match (strtoupper((string) $cabin)) {
            'ECONOMY' => 'Economy',
            'PREMIUM ECONOMY', 'PREMIUM_ECONOMY', 'PREMIUM' => 'Premium Economy',
            'BUSINESS' => 'Business',
            'FIRST' => 'First',
            'PREMIUM FIRST', 'PREMIUM_FIRST' => 'Premium First',
            default => null,
        };
    }

    private function normalizeFareType(?string $fareType): ?string
    {
        return match (strtolower((string) $fareType)) {
            'public' => 'public',
            'private' => 'private',
            'public_and_private', 'public+private', 'all' => 'public_and_private',
            default => null,
        };
    }

    private function normalizeString(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : $value;
    }

    private function normalizeIata(?string $value): ?string
    {
        $normalized = $this->normalizeString($value);

        return $normalized ? substr($normalized, 0, 3) : null;
    }

    private function normalizeCode(?string $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeCodes($value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => $this->normalizeCode($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $itinerary
     * @return array<int, string>
     */
    private function extractFlightNumbersFromItinerary(array $itinerary): array
    {
        return collect($itinerary)
            ->map(function ($segment) {
                $number = Arr::get($segment, 'flight_number') ?? Arr::get($segment, 'number');
                return $this->normalizeCode($number);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function parseBookingClasses(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return collect(preg_split('/[\s,]+/', strtoupper($value)))
            ->filter()
            ->map(fn ($code) => substr(trim($code), 0, 10))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    private function buildNdcBreakdown(array $ctx, Money $base, Money $total): array
    {
        $taxAmount = Arr::get($ctx, 'tax_amount');
        $currency = $total->currency();

        return [
            'currency' => Arr::get($ctx, 'currency', $currency),
            'base_amount' => $base->toFloat(),
            'tax_amount' => is_numeric($taxAmount) ? (float) $taxAmount : null,
            'total_amount' => $total->toFloat(),
        ];
    }
}
