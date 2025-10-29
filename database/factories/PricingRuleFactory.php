<?php

namespace Database\Factories;

use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingRule>
 */
class PricingRuleFactory extends Factory
{
    protected $model = PricingRule::class;

    public function definition(): array
    {
        return [
            'priority' => 0,
            'carrier' => $this->faker->randomElement(['SQ', 'EK', 'BA']),
            'usage' => PricingRule::USAGE_COMMISSION_BASE,
            'origin' => null,
            'destination' => null,
            'both_ways' => false,
            'travel_type' => 'OW+RT',
            'cabin_class' => null,
            'booking_class_rbd' => null,
            'booking_class_usage' => PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE,
            'passenger_types' => null,
            'sales_since' => null,
            'sales_till' => null,
            'departures_since' => null,
            'departures_till' => null,
            'returns_since' => null,
            'returns_till' => null,
            'fare_type' => 'public_and_private',
            'promo_code' => null,
            'calc_basis' => PricingRule::CALC_BASE_PRICE,
            'percent' => 5.0000,
            'flat_amount' => null,
            'fee_percent' => null,
            'fixed_fee' => null,
            'active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function forCarrier(string $carrier): self
    {
        return $this->state(fn () => ['carrier' => strtoupper($carrier)]);
    }

    public function discount(): self
    {
        return $this->state(fn () => [
            'usage' => PricingRule::USAGE_DISCOUNT_BASE,
            'percent' => 5.0000,
            'flat_amount' => null,
            'fee_percent' => null,
            'fixed_fee' => null,
        ]);
    }

    public function commissionWithDiscount(): self
    {
        return $this->state(fn () => [
            'usage' => PricingRule::USAGE_COMMISSION_DISCOUNT_BASE,
            'percent' => 8.0000,
            'flat_amount' => null,
            'fee_percent' => 2.5000,
            'fixed_fee' => 10.00,
        ]);
    }
}
