@php
    $usageOptions = $options['usage_options'] ?? [];
    $creationUsageOptions = $options['creation_usage_options'] ?? $usageOptions;
    $travelTypes = $options['travel_types'] ?? [];
    $fareTypes = $options['fare_types'] ?? [];
    $cabinClasses = $options['cabin_classes'] ?? [];
    $bookingClassUsageOptions = [
        \App\Models\PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE => 'At least one listed class must be in itinerary',
        \App\Models\PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED => 'Must not contain other than listed classes',
        \App\Models\PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED => 'Must not contain any of listed classes',
    ];
    $carrierOptions = $options['carriers'] ?? [];
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
    $carrierRuleDisplayOptions = array_merge($marketingRuleOptions, $operatingRuleOptions, [
        \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION => 'Without restrictions',
        \App\Models\PricingRule::AIRLINE_RULE_DIFFERENT_MARKETING => 'Different marketing carriers',
        \App\Models\PricingRule::AIRLINE_RULE_PLATING_ONLY => 'Plating carrier only',
        \App\Models\PricingRule::AIRLINE_RULE_OTHER_THAN_PLATING => 'Only other than plating carrier',
        \App\Models\PricingRule::AIRLINE_RULE_ONLY_LISTED => 'Only listed are authorized',
        \App\Models\PricingRule::AIRLINE_RULE_EXCLUDE_LISTED => 'Not operated by',
        \App\Models\PricingRule::AIRLINE_RULE_INCLUDE_ALL => 'Must contain all of the listed',
    ]);
    $flightRestrictionOptions = [
        \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE => 'Do not restrict',
        \App\Models\PricingRule::FLIGHT_RESTRICTION_ONLY_LISTED => 'Only listed flights',
        \App\Models\PricingRule::FLIGHT_RESTRICTION_EXCLUDE_LISTED => 'Exclude listed flights',
    ];
    $countryOptions = $options['countries'] ?? [
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BR' => 'Brazil',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CD' => 'Congo',
        'CG' => 'Congo',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => "Cote d'Ivoire",
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CW' => 'Curacao',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong (SAR), China',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'XK' => 'Kosovo',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libyan Arab Jamahiriya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macau (SAR), China',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'MK' => 'North Macedonia',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'PS' => 'Occupied Palestinian Territory',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn Islands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'VC' => 'Saint Vincent and the Grenadines',
        'BL' => 'Saint-Barthelemy',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'KR' => 'South Korea',
        'SS' => 'South Sudan',
        'ES' => 'Spain and Canary Islands',
        'LK' => 'Sri Lanka',
        'SH' => 'St. Helena',
        'KN' => 'St. Kitts and Nevis',
        'PM' => 'St. Pierre and Miquelon',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard and Jan Mayen Island',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TG' => 'Togo',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States of America',
        '00' => 'Unknown',
        'UY' => 'Uruguay',
        'UM' => 'US Minor Outlying Islands',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela',
        'VN' => 'Viet Nam',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'TL' => 'Vychodni Timor',
        'WF' => 'Wallis and Futuna Islands',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    ];
    $locationTypeOptions = [
        'airport' => 'Airport',
        'city' => 'City',
        'station' => 'Station',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Pricing Management') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div
            class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8"
            x-data="pricingRulesPage({
                createUrl: '{{ route('admin.pricing.rules.store') }}',
                updateBaseUrl: '{{ url('/admin/pricing/rules') }}',
                returnUrl: '{{ request()->fullUrl() }}'
            })"
        >
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Pricing rules feature</p>
                        <p class="text-sm text-gray-500">
                            Status:
                            <span class="font-medium {{ $featureEnabled ? 'text-emerald-600' : 'text-gray-500' }}">
                                {{ $featureEnabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </p>
                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-500">
                            <span>Total: <span class="font-semibold text-gray-700">{{ $stats['total'] }}</span></span>
                            <span>Active: <span class="font-semibold text-emerald-600">{{ $stats['active'] }}</span></span>
                            <span>Inactive: <span class="font-semibold text-rose-600">{{ $stats['inactive'] }}</span></span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.pricing.rules.create') }}"
                            class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                        >
                            Add a rule
                        </a>
                    </div>
                </div>

                @if (($commissionOverview ?? collect())->isNotEmpty())
                    <div class="mt-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-indigo-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold">Simple commission overview</p>
                                <p class="text-xs text-indigo-800">First active commission rule per carrier, ordered by priority.</p>
                            </div>
                            <span class="text-[11px] font-semibold text-indigo-700">Showing active commission rules only</span>
                        </div>
                        <div class="mt-3 overflow-x-auto rounded border border-indigo-100 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-indigo-100 text-sm">
                                <thead class="bg-indigo-50 text-xs uppercase tracking-wide text-indigo-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Carrier</th>
                                        <th class="px-3 py-2 text-left">% / Flat</th>
                                        <th class="px-3 py-2 text-left">Usage</th>
                                        <th class="px-3 py-2 text-left">Scope</th>
                                        <th class="px-3 py-2 text-left">Priority</th>
                                        <th class="px-3 py-2 text-left">Rule</th>
                                        <th class="px-3 py-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-indigo-50">
                                    @foreach ($commissionOverview as $rule)
                                        @php
                                            $usageLabel = $usageOptions[$rule->usage] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $rule->usage ?? ''));
                                            $carrierLabel = $rule->carrier ? $rule->carrier : 'All carriers';
                                            $scope = $rule->origin || $rule->destination
                                                ? ($rule->origin ?? 'Any') . ' → ' . ($rule->destination ?? 'Any')
                                                : 'Any route';
                                            if ($rule->both_ways) {
                                                $scope .= ' (both ways)';
                                            }
                                            $bookingScope = $rule->booking_class_rbd
                                                ? ($bookingClassUsageOptions[$rule->booking_class_usage] ?? 'Booking class filter')
                                                : 'Any booking class';
                                            $ruleData = [
                                                'id' => $rule->id,
                                                'priority' => $rule->priority,
                                                'carrier' => $rule->carrier ?? '',
                                                'plating_carrier' => $rule->plating_carrier ?? '',
                                                'marketing_carriers_rule' => $rule->marketing_carriers_rule ?? \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION,
                                                'marketing_carriers_rule_label' => $carrierRuleDisplayOptions[$rule->marketing_carriers_rule ?? \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION] ?? $carrierRuleDisplayOptions[\App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION],
                                                'operating_carriers_rule' => $rule->operating_carriers_rule ?? \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION,
                                                'operating_carriers_rule_label' => $carrierRuleDisplayOptions[$rule->operating_carriers_rule ?? \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION] ?? $carrierRuleDisplayOptions[\App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION],
                                                'marketing_carriers' => $rule->marketing_carriers ?? [],
                                                'operating_carriers' => $rule->operating_carriers ?? [],
                                                'flight_restriction_type' => $rule->flight_restriction_type ?? \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE,
                                                'flight_restriction_type_label' => $flightRestrictionOptions[$rule->flight_restriction_type ?? \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE] ?? $flightRestrictionOptions[\App\Models\PricingRule::FLIGHT_RESTRICTION_NONE],
                                                'flight_numbers' => $rule->flight_numbers ?? '',
                                                'usage' => $rule->usage,
                                                'usage_label' => $usageLabel,
                                                'origin' => $rule->origin,
                                                'destination' => $rule->destination,
                                                'both_ways' => (bool) $rule->both_ways,
                                                'travel_type' => $rule->travel_type,
                                                'travel_type_label' => $travelTypes[$rule->travel_type] ?? ($rule->travel_type ?? '—'),
                                                'cabin_class' => $rule->cabin_class,
                                                'cabin_class_label' => $rule->cabin_class ?? '—',
                                                'booking_class_rbd' => $rule->booking_class_rbd,
                                                'booking_class_usage' => $rule->booking_class_usage,
                                                'booking_class_usage_label' => $bookingClassUsageOptions[$rule->booking_class_usage] ?? ($rule->booking_class_usage ?? '—'),
                                                'passenger_types' => $rule->passenger_types ?? [],
                                                'sales_since' => $rule->sales_since?->format('Y-m-d\TH:i'),
                                                'sales_till' => $rule->sales_till?->format('Y-m-d\TH:i'),
                                                'departures_since' => $rule->departures_since?->format('Y-m-d\TH:i'),
                                                'departures_till' => $rule->departures_till?->format('Y-m-d\TH:i'),
                                                'returns_since' => $rule->returns_since?->format('Y-m-d\TH:i'),
                                                'returns_till' => $rule->returns_till?->format('Y-m-d\TH:i'),
                                                'fare_type' => $rule->fare_type,
                                                'fare_type_label' => $fareTypes[$rule->fare_type] ?? ($rule->fare_type ?? '—'),
                                                'promo_code' => $rule->promo_code,
                                                'percent' => $rule->percent,
                                                'flat_amount' => $rule->flat_amount,
                                                'fee_percent' => $rule->fee_percent,
                                                'fixed_fee' => $rule->fixed_fee,
                                                'is_primary_pcc' => (bool) $rule->is_primary_pcc,
                                                'active' => (bool) $rule->active,
                                                'notes' => $rule->notes,
                                            ];
                                        @endphp
                                        <tr class="bg-white">
                                            <td class="px-3 py-2 font-semibold text-gray-800">
                                                @if ($rule->carrier)
                                                    {{ $carrierLabel }}
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-800">
                                                        {{ $carrierLabel }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700">
                                                <div class="flex flex-col">
                                                    <span>{{ $rule->percent !== null ? number_format((float) $rule->percent, 2) . '%' : '—' }}</span>
                                                    <span>{{ $rule->flat_amount !== null ? number_format((float) $rule->flat_amount, 2) : '—' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700">{{ $usageLabel }}</td>
                                            <td class="px-3 py-2 text-gray-700">
                                                <div class="flex flex-col text-xs">
                                                    <span>{{ $scope }}</span>
                                                    <span>{{ $bookingScope }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700">#{{ $rule->priority }}</td>
                                            <td class="px-3 py-2 text-gray-700">
                                            <a href="{{ route('admin.pricing.index', array_merge(request()->except('page'), ['tab' => 'rules', 'carrier' => $rule->carrier ?? ''])) }}"
                                               class="text-indigo-600 hover:underline">
                                                    View rule #{{ $rule->id }}
                                                </a>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700">
                                                <div class="flex gap-3">
                                                    <button type="button"
                                                        class="text-sm font-semibold text-indigo-600 hover:underline"
                                                        @click="openEdit(@js($ruleData))">
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.pricing.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                                        <button type="submit" class="text-sm font-semibold text-rose-600 hover:underline">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-6 text-sm text-gray-700">
                    <p class="font-semibold text-gray-800">Detailed rule list hidden</p>
                    <p class="mt-1">To keep commissions simple, only the overview above is shown. Use “Add a rule” to create or update a commission.</p>
                </div>
            </div>

            <x-modal name="pricing-rule-modal" :show="false">
                <div class="p-6">
                    <form method="POST" :action="$store.pricingRules.formAction()" class="space-y-6" x-ref="ruleForm">
                        @csrf
                        <template x-if="$store.pricingRules.mode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
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

                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800" x-text="$store.pricingRules.modeTitle()"></h2>
                                <p class="text-sm text-gray-500">Set the scope and action for this pricing adjustment.</p>
                            </div>
                            <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="$dispatch('close-modal', 'pricing-rule-modal')">
                                Close
                            </button>
                        </div>

                        @include('admin.pricing.partials.rule-form-fields')

                        <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input id="rule_active" type="checkbox" name="active" value="1" x-model="$store.pricingRules.form.active"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Rule is active</span>
                            </label>

                            <div class="flex justify-end gap-3">
                                <button type="button" class="text-sm text-gray-600 hover:text-gray-800" @click="$dispatch('close-modal', 'pricing-rule-modal')">
                                    Cancel
                                </button>
                                <x-primary-button x-text="$store.pricingRules.mode === 'edit' ? 'Update rule' : 'Create rule'"></x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </x-modal>

            <x-modal name="pricing-rule-detail" :show="false">
                <div class="space-y-4 p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Pricing rule detail</h2>
                        <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="$dispatch('close-modal', 'pricing-rule-detail')">
                            Close
                        </button>
                    </div>
                    <div class="grid gap-4 text-sm text-gray-700">
                        <dl class="grid gap-2">
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Rule ID</dt>
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.id ? `#${$store.pricingRules.detail.id}` : '—'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Carrier</dt>
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.carrier || 'All carriers'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Plating carrier</dt>
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.plating_carrier || '—'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Marketing carriers</dt>
                                <dd class="col-span-2">
                                    <div class="font-semibold" x-text="$store.pricingRules.detail.marketing_carriers_rule_label || 'Without restrictions'"></div>
                                    <div class="text-xs text-gray-500" x-show="$store.pricingRules.detail.marketing_carriers_list" x-text="$store.pricingRules.detail.marketing_carriers_list"></div>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Operating carriers</dt>
                                <dd class="col-span-2">
                                    <div class="font-semibold" x-text="$store.pricingRules.detail.operating_carriers_rule_label || 'Without restrictions'"></div>
                                    <div class="text-xs text-gray-500" x-show="$store.pricingRules.detail.operating_carriers_list" x-text="$store.pricingRules.detail.operating_carriers_list"></div>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Usage</dt>
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.usage_label || $store.pricingRules.detail.usage || '—'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Scope</dt>
                                <dd class="col-span-2">
                                    <span x-text="$store.pricingRules.detail.origin || 'Any'"></span>
                                    →
                                    <span x-text="$store.pricingRules.detail.destination || 'Any'"></span>
                                    <span class="ml-2 rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600" x-text="$store.pricingRules.detail.both_ways ? 'Both ways' : 'One direction'"></span>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Flight restriction</dt>
                                <dd class="col-span-2">
                                    <div class="flex flex-col">
                                        <span class="font-semibold" x-text="$store.pricingRules.detail.flight_restriction_type_label || 'Do not restrict'"></span>
                                        <span class="text-xs text-gray-500" x-text="$store.pricingRules.detail.flight_numbers || '—'"></span>
                                    </div>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Travel</dt>
                                <dd class="col-span-2">
                                    <span class="font-semibold" x-text="$store.pricingRules.detail.travel_type_label || $store.pricingRules.detail.travel_type || '—'"></span>,
                                    cabin <span class="font-semibold" x-text="$store.pricingRules.detail.cabin_class_label || '—'"></span>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Booking class</dt>
                                <dd class="col-span-2">
                                    <span class="font-semibold" x-text="$store.pricingRules.detail.booking_class_rbd || '—'"></span>
                                    <span class="ml-2 text-xs text-gray-500" x-text="$store.pricingRules.detail.booking_class_usage_label || '—'"></span>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Fare type</dt>
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.fare_type_label || '—'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Values</dt>
                                <dd class="col-span-2">
                                    <div class="flex gap-3">
                                        <span>Percent: <span class="font-semibold" x-text="$store.pricingRules.detail.percent ?? '—'"></span></span>
                                        <span>Flat: <span class="font-semibold" x-text="$store.pricingRules.detail.flat_amount ?? '—'"></span></span>
                                        <span>Fee %: <span class="font-semibold" x-text="$store.pricingRules.detail.fee_percent ?? '—'"></span></span>
                                        <span>Fixed fee: <span class="font-semibold" x-text="$store.pricingRules.detail.fixed_fee ?? '—'"></span></span>
                                    </div>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Windows</dt>
                                <dd class="col-span-2 space-y-1">
                                    <p>Sales: <span x-text="$store.pricingRules.detail.sales_since || '—'"></span> → <span x-text="$store.pricingRules.detail.sales_till || '—'"></span></p>
                                    <p>Departures: <span x-text="$store.pricingRules.detail.departures_since || '—'"></span> → <span x-text="$store.pricingRules.detail.departures_till || '—'"></span></p>
                                    <p>Returns: <span x-text="$store.pricingRules.detail.returns_since || '—'"></span> → <span x-text="$store.pricingRules.detail.returns_till || '—'"></span></p>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Passenger types</dt>
                                <dd class="col-span-2" x-text="($store.pricingRules.detail.passenger_types || []).join(', ') || 'All'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Promo code</dt>
                                <dd class="col-span-2" x-text="$store.pricingRules.detail.promo_code || '—'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Multi PCC</dt>
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.is_primary_pcc ? 'Primary' : 'Not primary'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Status</dt>
                                <dd class="col-span-2">
                                    <span class="rounded px-2 py-1 text-xs font-semibold" :class="$store.pricingRules.detail.active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                          x-text="$store.pricingRules.detail.active ? 'Active' : 'Disabled'"></span>
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Notes</dt>
                                <dd class="col-span-2 whitespace-pre-line" x-text="$store.pricingRules.detail.notes || '—'"></dd>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">Actions</dt>
                                <dd class="col-span-2">
                                    <form method="POST" :action="`${$store.pricingRules.config.updateBaseUrl}/${$store.pricingRules.detail.id}`" onsubmit="return confirm('Delete this rule?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_url" :value="$store.pricingRules.config.returnUrl">
                                        <button type="submit" class="text-sm font-semibold text-rose-600 hover:underline">Delete rule</button>
                                    </form>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>

    @include('admin.pricing.partials.pricing-rule-scripts')
</x-app-layout>
