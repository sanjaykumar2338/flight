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
    $carrierRuleOptions = [
        \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION => 'Without restrictions',
        \App\Models\PricingRule::AIRLINE_RULE_ONLY_LISTED => 'Only listed carriers',
        \App\Models\PricingRule::AIRLINE_RULE_EXCLUDE_LISTED => 'Exclude listed carriers',
    ];
    $flightRestrictionOptions = [
        \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE => 'Do not restrict',
        \App\Models\PricingRule::FLIGHT_RESTRICTION_ONLY_LISTED => 'Only listed flights',
        \App\Models\PricingRule::FLIGHT_RESTRICTION_EXCLUDE_LISTED => 'Exclude listed flights',
    ];
    $carrierOptions = $options['carriers'] ?? [];
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
        <div
            class="mx-auto max-w-5xl sm:px-6 lg:px-8"
            x-data="pricingRuleCreatePage({
                createUrl: '{{ route('admin.pricing.rules.store') }}',
                updateBaseUrl: '{{ url('/admin/pricing/rules') }}',
                returnUrl: '{{ $returnUrl ?? route('admin.pricing.index') }}'
            })"
        >
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form method="POST" :action="$store.pricingRules.formAction()" class="space-y-6" x-ref="ruleForm">
                    @csrf
                    <input type="hidden" name="return_url" :value="$store.pricingRules.config.returnUrl">

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

                    @include('admin.pricing.partials.rule-form-fields')

                    <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input id="rule_active" type="checkbox" name="active" value="1" x-model="$store.pricingRules.form.active"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Rule is active</span>
                        </label>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.pricing.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <x-primary-button>Create rule</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.pricing.partials.pricing-rule-scripts')
</x-app-layout>
