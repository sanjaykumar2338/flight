<?php

use App\Models\AirlineCommission;
use App\Models\PricingRule;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'pricing.rules.enabled' => true,
        'pricing.rules.cache_ttl' => 0,
    ]);
});

function passengerSummary(array $types = ['ADT' => 1]): array
{
    return [
        'types' => array_map(fn ($count) => (int) $count, $types),
        'list' => array_keys(array_filter($types, fn ($count) => $count > 0)),
        'total' => array_sum($types),
    ];
}

function pricingContext(array $overrides = []): array
{
    return array_merge([
        'carrier' => 'SQ',
        'origin' => 'SIN',
        'destination' => 'BKK',
        'travel_type' => 'OW',
        'cabin_class' => 'Economy',
        'fare_type' => 'public_and_private',
        'promo_code' => null,
        'sales_date' => '2025-01-01T00:00:00Z',
        'departure_date' => '2025-02-01',
        'return_date' => null,
        'booking_class_rbd' => null,
    ], $overrides);
}

function pricingService(): PricingService
{
    return app(PricingService::class);
}

it('applies commission rule using pricing engine', function () {
    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 10,
        'priority' => 10,
    ]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        100,
        20,
        'USD',
        pricingContext()
    );

    expect($result['engine']['used'])->toBeTrue()
        ->and($result['payable_total'])->toBe(130.0)
        ->and($result['components']['adjustments'])->toBe(10.0)
        ->and($result['rules_applied'])->toHaveCount(1);
});

it('applies discount rule reducing total', function () {
    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_DISCOUNT_BASE,
        'percent' => 5,
        'priority' => 5,
    ]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        200,
        0,
        'USD',
        pricingContext()
    );

    expect($result['engine']['used'])->toBeTrue()
        ->and($result['payable_total'])->toBe(190.0)
        ->and($result['components']['adjustments'])->toBe(-10.0);
});

it('applies commission then discount in priority order', function () {
    $commission = PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 10,
        'priority' => 5,
    ]);

    $discount = PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_DISCOUNT_BASE,
        'percent' => 5,
        'priority' => 10,
    ]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        200,
        0,
        'USD',
        pricingContext()
    );

    expect($result['engine']['used'])->toBeTrue()
        ->and($result['payable_total'])->toBe(210.0)
        ->and($result['rules_applied'])->sequence(
            fn ($rule) => $rule
                ->id->toBe($commission->id)
                ->impact_amount->toBe(20.0),
            fn ($rule) => $rule
                ->id->toBe($discount->id)
                ->impact_amount->toBe(-10.0)
        );
});

it('applies fee alongside commission', function () {
    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 8,
        'priority' => 5,
    ]);

    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_DISCOUNT_BASE,
        'percent' => 0,
        'fee_percent' => 2,
        'fixed_fee' => 5,
        'priority' => 6,
    ]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        150,
        20,
        'USD',
        pricingContext()
    );

    expect($result['engine']['used'])->toBeTrue()
        ->and(round($result['components']['adjustments'], 2))->toBe(4.0)
        ->and(round($result['payable_total'], 2))->toBe(174.0);
});

it('respects sales and departure windows', function () {
    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'sales_since' => '2025-01-01 00:00:00',
        'sales_till' => '2025-01-20 23:59:59',
        'departures_since' => '2025-02-01 00:00:00',
        'departures_till' => '2025-02-28 23:59:59',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 5,
        'priority' => 1,
    ]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        300,
        30,
        'USD',
        pricingContext([
            'sales_date' => '2025-01-10T00:00:00Z',
            'departure_date' => '2025-02-10',
        ])
    );

    expect($result['engine']['used'])->toBeTrue()
        ->and($result['rules_applied'])->toHaveCount(1);

    $outsideWindow = pricingService()->calculate(
        [],
        passengerSummary(),
        300,
        30,
        'USD',
        pricingContext([
            'sales_date' => '2025-03-01T00:00:00Z',
            'departure_date' => '2025-04-01',
        ])
    );

    expect($outsideWindow['engine']['used'])->toBeFalse();
});

it('honours booking class include and exclude rules', function () {
    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 5,
        'booking_class_rbd' => 'Y',
        'booking_class_usage' => PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE,
        'priority' => 1,
    ]);

    $include = pricingService()->calculate(
        [],
        passengerSummary(),
        200,
        20,
        'USD',
        pricingContext(['booking_class_rbd' => 'Y'])
    );

    expect($include['engine']['used'])->toBeTrue();

    $excludeRule = PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 5,
        'booking_class_rbd' => 'Z',
        'booking_class_usage' => PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED,
        'priority' => 2,
    ]);

    $excluded = pricingService()->calculate(
        [],
        passengerSummary(),
        200,
        20,
        'USD',
        pricingContext(['booking_class_rbd' => 'Z'])
    );

    expect($excluded['engine']['used'])->toBeFalse();
});

it('applies more specific rule before generic rule when priorities match', function () {
    $specific = PricingRule::factory()->create([
        'carrier' => 'SQ',
        'origin' => 'SIN',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 5,
        'priority' => 50,
    ]);

    $generic = PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'percent' => 5,
        'priority' => 50,
    ]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        100,
        0,
        'USD',
        pricingContext()
    );

    expect($result['rules_applied'])->sequence(
        fn ($rule) => $rule->id->toBe($specific->id),
        fn ($rule) => $rule->id->toBe($generic->id)
    );
});

it('falls back to legacy commission when no rule matches', function () {
    config(['pricing.rules.enabled' => true, 'pricing.defaults.markup_percent' => 5]);

    $result = pricingService()->calculate(
        [],
        passengerSummary(),
        100,
        0,
        'USD',
        pricingContext(['carrier' => 'AB'])
    );

    expect($result['engine']['used'])->toBeFalse()
        ->and(round($result['components']['adjustments'], 2))->toBe(5.0)
        ->and(data_get($result, 'legacy.source'))->toBe('default');

    AirlineCommission::factory()->create([
        'airline_code' => 'CD',
        'markup_percent' => 12,
        'flat_markup' => 0,
        'is_active' => true,
    ]);

    $withLegacyRecord = pricingService()->calculate(
        [],
        passengerSummary(),
        100,
        0,
        'USD',
        pricingContext(['carrier' => 'CD'])
    );

    expect($withLegacyRecord['engine']['used'])->toBeFalse()
        ->and(round($withLegacyRecord['components']['adjustments'], 2))->toBe(12.0)
        ->and(data_get($withLegacyRecord, 'legacy.source'))->toBe('airline');
});
