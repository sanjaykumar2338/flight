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
