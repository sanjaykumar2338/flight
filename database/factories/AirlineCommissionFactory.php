<?php

namespace Database\Factories;

use App\Models\AirlineCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AirlineCommission>
 */
class AirlineCommissionFactory extends Factory
{
    protected $model = AirlineCommission::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->lexify('??'));

        return [
            'airline_code' => $code,
            'airline_name' => $this->faker->company(),
            'markup_percent' => $this->faker->randomFloat(2, 1, 15),
            'flat_markup' => $this->faker->randomFloat(2, 5, 50),
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
