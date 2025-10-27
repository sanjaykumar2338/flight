<?php

use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcClient;

it('returns canned response in demo mode', function () {
    $client = new TravelNdcClient(null, [
        'mode' => 'demo',
        'demo_responses' => [
            'airshopping' => '<Demo/>',
        ],
    ]);

    expect($client->post('airshopping', '<Request/>'))->toBe('<Demo/>');
});

it('falls back to the Postman sample when config is empty', function () {
    $client = new TravelNdcClient(null, [
        'mode' => 'demo',
        'demo_responses' => [],
    ]);

    $response = $client->post('airshopping', '<Request/>');

    expect($response)->toContain('<AirShoppingRS');
});

it('throws when no demo response exists', function () {
    $client = new TravelNdcClient(null, [
        'mode' => 'demo',
        'demo_responses' => [],
    ]);

    $client->post('unknown-endpoint', '<Request/>');
})->throws(TravelNdcException::class, 'No demo response available for unknown-endpoint endpoint.');
