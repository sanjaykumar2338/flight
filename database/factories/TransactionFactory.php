<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'provider' => 'paystack',
            'mode' => 'sandbox',
            'reference' => $this->faker->uuid(),
            'amount' => 450,
            'currency' => 'USD',
            'status' => 'init',
            'raw_payload' => null,
        ];
    }
}
