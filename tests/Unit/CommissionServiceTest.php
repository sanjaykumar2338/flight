<?php

use App\Models\AirlineCommission;
use App\Services\Pricing\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('normalizes airline codes before looking up commissions', function () {
    AirlineCommission::factory()->create([
        'airline_code' => 'SQ',
        'markup_percent' => 10,
        'flat_markup' => 5,
        'is_active' => true,
    ]);

    $service = new CommissionService();

    $result = $service->pricingForAirline('  sq  ', 200);

    expect($result)
        ->airline_code->toBe('SQ')
        ->percent_rate->toBe(10.0)
        ->markup_amount->toBe(25.0)
        ->source->toBe('airline');
});
