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
            'pricing_json' => json_encode(['total' => 450], JSON_THROW_ON_ERROR),
        ];
    }
}
