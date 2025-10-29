<?php

use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['pricing.rules.enabled' => true]);
});

function adminUser(): User
{
    return User::factory()->create(['is_admin' => true]);
}

it('saves a pricing rule with dropdown selections', function () {
    $user = adminUser();

    $response = $this->actingAs($user)
        ->post(route('admin.pricing.rules.store'), [
            'priority' => 25,
            'carrier' => 'SQ',
            'usage' => PricingRule::USAGE_COMMISSION_DISCOUNT_BASE,
            'origin' => 'SIN',
            'destination' => 'BKK',
            'both_ways' => '1',
            'travel_type' => 'RT',
            'cabin_class' => 'Premium Economy',
            'booking_class_rbd' => 'Y',
            'booking_class_usage' => PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED,
            'fare_type' => 'private',
            'passenger_types' => ['ADT', 'CHD'],
            'percent' => 8,
            'fee_percent' => 2,
            'fixed_fee' => 5,
            'notes' => 'Test rule',
            'active' => '1',
        ]);

    $response->assertRedirect();

    tap(PricingRule::latest()->first(), function (PricingRule $rule) {
        expect($rule->carrier)->toBe('SQ')
            ->and($rule->usage)->toBe(PricingRule::USAGE_COMMISSION_DISCOUNT_BASE)
            ->and($rule->travel_type)->toBe('RT')
            ->and($rule->cabin_class)->toBe('Premium Economy')
            ->and($rule->booking_class_rbd)->toBe('Y')
            ->and($rule->booking_class_usage)->toBe(PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED)
            ->and($rule->fare_type)->toBe('private')
            ->and($rule->passenger_types)->toBe(['ADT', 'CHD'])
            ->and($rule->percent)->toBe('8.0000')
            ->and($rule->fee_percent)->toBe('2.0000')
            ->and($rule->fixed_fee)->toBe('5.00')
            ->and($rule->both_ways)->toBeTrue();
    });
});

it('filters pricing rules by carrier usage and fare type', function () {
    $user = adminUser();

    PricingRule::factory()->create([
        'carrier' => 'SQ',
        'usage' => PricingRule::USAGE_COMMISSION_BASE,
        'fare_type' => 'public',
        'percent' => 5,
    ]);

    PricingRule::factory()->create([
        'carrier' => 'EK',
        'usage' => PricingRule::USAGE_DISCOUNT_BASE,
        'fare_type' => 'private',
        'percent' => 5,
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.pricing.index', [
            'tab' => 'rules',
            'carrier' => 'EK',
            'usage' => PricingRule::USAGE_DISCOUNT_BASE,
            'fare_type' => 'private',
        ]));

    $response->assertStatus(200)
        ->assertSee('EK')
        ->assertViewHas('rules', function ($rules) {
            return $rules->every(fn ($rule) => $rule->carrier === 'EK');
        });
});
