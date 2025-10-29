<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'airline_code' => strtoupper($this->faker->lexify('??')),
            'currency' => 'USD',
            'customer_email' => $this->faker->safeEmail(),
            'customer_name' => $this->faker->name(),
            'amount_base' => 400,
            'amount_final' => 450,
            'status' => 'pending',
            'priced_offer_ref' => $this->faker->uuid(),
            'primary_carrier' => strtoupper($this->faker->lexify('??')),
            'itinerary_json' => json_encode(['segments' => []], JSON_THROW_ON_ERROR),
            'pricing_json' => json_encode([
                'ndc' => [
                    'base_amount' => 400,
                    'tax_amount' => 40,
                    'total_amount' => 440,
                ],
                'payable_total' => 450,
                'components' => [
                    'base_fare' => 400,
                    'taxes' => 40,
                    'adjustments' => 10,
                ],
                'rules_applied' => [],
                'engine' => [
                    'enabled' => false,
                    'used' => false,
                    'result' => null,
                ],
                'legacy' => [
                    'commission_amount' => 10,
                    'percent_rate' => 2.5,
                    'flat_component' => 0,
                    'source' => 'default',
                ],
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
