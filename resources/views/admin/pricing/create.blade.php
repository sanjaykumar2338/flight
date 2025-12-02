@php
    $usageOptions = $options['usage_options'] ?? [];
    $creationUsageOptions = $options['creation_usage_options'] ?? $usageOptions;
    $fareTypes = $options['fare_types'] ?? [];
    $cabinClasses = $options['cabin_classes'] ?? [];
    $bookingClassUsageOptions = [
        \App\Models\PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE => 'At least one listed class must be in itinerary',
        \App\Models\PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED => 'Must not contain other than listed classes',
        \App\Models\PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED => 'Must not contain any of listed classes',
    ];
    $marketingRuleOptions = [
        '' => 'Without restrictions',
        'Y' => 'Different marketing carriers',
        'N' => 'Plating carrier only',
        'D' => 'Only other than plating carrier',
    ];
    $operatingRuleOptions = [
        '' => 'Without restrictions',
        'S' => 'Only listed are authorized',
        'N' => 'Not operated by',
        'A' => 'Must contain all of the listed',
    ];
    $flightRestrictionOptions = [
        \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE => 'Do not restrict',
        \App\Models\PricingRule::FLIGHT_RESTRICTION_ONLY_LISTED => 'Only listed flights',
        \App\Models\PricingRule::FLIGHT_RESTRICTION_EXCLUDE_LISTED => 'Exclude listed flights',
    ];
    $carrierOptions = $options['carriers'] ?? [];
    $countryOptions = $options['countries'] ?? []; // Assuming empty is fine for create
    $locationTypeOptions = [ 'airport' => 'Airport', 'city' => 'City', 'station' => 'Station' ];

    // Provide default values for the create form
    $ruleFields = [
        'priority' => old('priority', 0),
        'usage' => old('usage', 'commission_base'),
        'notes' => old('notes', ''),
        'carrier' => old('carrier', ''),
        'plating_carrier' => old('plating_carrier', ''),
        'marketing_carriers_rule' => old('marketing_carriers_rule', ''),
        'marketing_carriers' => old('marketing_carriers', ''),
        'operating_carriers_rule' => old('operating_carriers_rule', ''),
        'operating_carriers' => old('operating_carriers', ''),
        'flight_restriction_type' => old('flight_restriction_type', \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE),
        'flight_numbers' => old('flight_numbers', ''),
        'percent' => old('percent', ''),
        'flat_amount' => old('flat_amount', ''),
        'fee_percent' => old('fee_percent', ''),
        'fixed_fee' => old('fixed_fee', ''),
        'promo_code' => old('promo_code', ''),
        'origin' => old('origin', ''),
        'destination' => old('destination', ''),
        'both_ways' => old('both_ways', false),
        'travel_type' => old('travel_type', 'OW+RT'),
        'cabin_class' => old('cabin_class', ''),
        'booking_class_usage' => old('booking_class_usage', \App\Models\PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE),
        'booking_class_rbd' => old('booking_class_rbd', ''),
        'passenger_types' => old('passenger_types', []),
        'fare_type' => old('fare_type', 'public_and_private'),
        'sales_since' => old('sales_since', ''),
        'sales_till' => old('sales_till', ''),
        'departures_since' => old('departures_since', ''),
        'departures_till' => old('departures_till', ''),
        'returns_since' => old('returns_since', ''),
        'returns_till' => old('returns_till', ''),
        'is_primary_pcc' => old('is_primary_pcc', '0'),
        'active' => old('active', true),
    ];
@endphp



<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ __('Create pricing rule') }}
                </h2>
                <p class="text-sm text-gray-500">Set scope and amounts for a new pricing rule.</p>
            </div>
            <a href="{{ route('admin.pricing.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Back to list</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.pricing.rules.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ $returnUrl }}">

                    @if ($errors->any())
                        <div class="rounded border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                            <p class="font-semibold">Please fix the errors below.</p>
                            <ul class="mt-2 space-y-1 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('admin.pricing.partials.rule-form-fields-simple')

                    <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input id="rule_active" type="checkbox" name="active" value="1" @if($ruleFields['active']) checked @endif
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Rule is active</span>
                        </label>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.pricing.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <x-primary-button>
                                Create rule
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
