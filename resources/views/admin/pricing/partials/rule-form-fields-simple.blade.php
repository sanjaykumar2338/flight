@php
    // This is a simplified version of the form fields, with all Alpine.js logic removed.
    // Values are populated using old() or the $ruleFields array defined in the parent view.
    $platingCarrierRuleOptions = $platingCarrierRuleOptions ?? [
        '' => 'Without restrictions',
        'only' => 'Only listed are authorized',
        'except' => 'Not operated by',
    ];
    $ruleFields = $ruleFields ?? [];
    $ruleFields['carrier'] = $ruleFields['carrier'] ?? '';
    $ruleFields['marketing_carriers_rule'] = $ruleFields['marketing_carriers_rule'] ?? '';
    $ruleFields['marketing_carriers'] = $ruleFields['marketing_carriers'] ?? '';
    $ruleFields['operating_carriers_rule'] = $ruleFields['operating_carriers_rule'] ?? '';
    $ruleFields['operating_carriers'] = $ruleFields['operating_carriers'] ?? '';
    $carrierOptions = $carrierOptions ?? [];
    $airlinesFile = base_path('airlines.php');
    $airlines = [];
    if (file_exists($airlinesFile)) {
        $loaded = include $airlinesFile; // returns array when file returns [...]
        if (is_array($loaded)) {
            $airlines = $loaded;
        }
    }
    // Fallback to a JSON copy in public if available.
    if (empty($airlines)) {
        $jsonFile = public_path('airlines.json');
        if (file_exists($jsonFile)) {
            $json = json_decode(file_get_contents($jsonFile), true);
            if (is_array($json)) {
                $airlines = $json;
            }
        }
    }

    // If no options provided, use full airlines list.
    if (!empty($airlines) && is_array($airlines)) {
        // Prefer the full airlines list so the dropdown always shows every carrier.
        $carrierOptions = $airlines;
    }

    // If we only have codes, map them to labels from airlines.php when available.
    if (!empty($carrierOptions)) {
        $mapped = [];
        foreach ($carrierOptions as $code => $label) {
            $carrierCode = is_int($code) ? $label : $code;
            $display = $airlines[$carrierCode] ?? (is_string($label) ? $label : $carrierCode);
            $mapped[$carrierCode] = $display;
        }
        $carrierOptions = $mapped;
    }
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
        'ALL' => 'Do not restrict',
        'EXISTS' => 'An itinerary must include the flight number',
        'EXCLUDE' => 'An itinerary must not include the flight number',
        'ONLY' => 'An itinerary must include only the specified flight numbers',
    ];
    $countryOptions = $countryOptions ?? [];
    $countriesFile = public_path('countries.json');
    if (file_exists($countriesFile)) {
        $countriesJson = json_decode(file_get_contents($countriesFile), true);
        if (is_array($countriesJson) && !empty($countriesJson)) {
            $countryOptions = $countriesJson;
        }
    }
    $locationTypeOptions = $locationTypeOptions ?? [];
    $destTypesFile = public_path('destination-types.json');
    if (file_exists($destTypesFile)) {
        $destTypesJson = json_decode(file_get_contents($destTypesFile), true);
        if (is_array($destTypesJson) && !empty($destTypesJson)) {
            $locationTypeOptions = $destTypesJson;
        }
    }
@endphp

<div class="space-y-5">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Rule Details</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_priority" value="Priority" />
                    <input id="rule_priority" name="priority" type="number" min="0" max="1000" value="{{ $ruleFields['priority'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('priority')" class="mt-1" />
                </div>
                <div class="hidden md:block" aria-hidden="true"></div>
                <div class="md:col-span-2">
                    <x-input-label value="Usage" />
                    <div class="mt-2 grid gap-2 rounded border border-gray-200 bg-white p-3">
                        @foreach ($creationUsageOptions as $value => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="usage" value="{{ $value }}" @if($ruleFields['usage'] == $value) checked @endif
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('usage')" class="mt-1" />
                </div>
            </div>
        </div>
    </div>

    <fieldset class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <legend class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700 w-full">Airlines</legend>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="rule_carrier" value="Plating carrier" />
                    <select id="rule_carrier" name="carrier" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @if(!empty($carrierOptions))
                            @foreach ($carrierOptions as $code => $label)
                                @php
                                    $value = is_int($code) ? $label : $code;
                                    $text = is_string($label) ? $label : $value;
                                    if (is_int($code) && is_string($label) && str_contains($label, ' - ')) {
                                        $value = trim(explode(' - ', $label, 2)[0]);
                                    }
                                @endphp
                                <option value="{{ $value }}" @if(($ruleFields['carrier'] ?? '') == $value) selected @endif>{{ $text }}</option>
                            @endforeach
                        @else
                            @include('admin.pricing.partials.carrier-options')
                        @endif
                    </select>
                    <x-input-error :messages="$errors->get('carrier')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_marketing_carriers_rule" value="Marketing carriers" />
                    <select id="rule_marketing_carriers_rule" name="marketing_carriers_rule"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($marketingRuleOptions as $value => $label)
                            <option value="{{ $value }}" @if(($ruleFields['marketing_carriers_rule'] ?? '') == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('marketing_carriers_rule')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_operating_carriers_rule" value="Operating carriers" />
                    <select id="rule_operating_carriers_rule" name="operating_carriers_rule"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($operatingRuleOptions as $value => $label)
                            <option value="{{ $value }}" @if(($ruleFields['operating_carriers_rule'] ?? '') == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('operating_carriers_rule')" class="mt-1" />
                </div>
                <div id="operating_carriers_container" class="md:col-span-3" style="display:none;">
                    <x-input-label for="rule_operating_carriers" value="Carrier IATA code(s)" />
                    <input id="rule_operating_carriers" name="operating_carriers"
                           placeholder="Multiple codes separated by comma"
                           value="{{ $ruleFields['operating_carriers'] ?? '' }}"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">(multiple codes separated by comma)</p>
                    <x-input-error :messages="$errors->get('operating_carriers')" class="mt-1" />
                </div>
            </div>
        </div>
    </fieldset>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Flight restriction</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_flight_restriction_type" value="Flights" />
                    <select id="rule_flight_restriction_type" name="flight_restriction_type"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($flightRestrictionOptions as $value => $label)
                            <option value="{{ $value }}" @if($ruleFields['flight_restriction_type'] == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('flight_restriction_type')" class="mt-1" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label value="Flight numbers" />
                    <div id="flight_rows" class="space-y-2"></div>
                    <button type="button" id="add_flight_row" class="mt-2 rounded border border-orange-500 px-4 py-2 text-orange-600 hover:bg-orange-50">
                        Add flight
                    </button>
                    <input type="hidden" id="rule_flight_numbers" name="flight_numbers" value="{{ $ruleFields['flight_numbers'] }}">
                    <p class="mt-1 text-xs text-gray-500">Enter carrier code and flight number ranges. Multiple codes separated by comma in carrier field.</p>
                    <x-input-error :messages="$errors->get('flight_numbers')" class="mt-1" />
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Commission / Discount amount</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                @php
                    $amountMode = !empty($ruleFields['flat_amount']) ? 'abs' : 'pct';
                @endphp
                <div>
                    <x-input-label for="rule_amount_mode" value="Commission / Discount" />
                    <select id="rule_amount_mode" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="abs" @if($amountMode==='abs') selected @endif>Fixed</option>
                        <option value="pct" @if($amountMode==='pct') selected @endif>%</option>
                    </select>
                </div>
                <div id="percent_container">
                    <x-input-label for="rule_percent" value="Percent" />
                    <input id="rule_percent" name="percent" type="number" step="0.0001" min="0" max="100" value="{{ $ruleFields['percent'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('percent')" class="mt-1" />
                </div>
                <div id="flat_container">
                    <x-input-label for="rule_flat_amount" value="Fixed amount" />
                    <input id="rule_flat_amount" name="flat_amount" type="number" step="0.01" min="0" value="{{ $ruleFields['flat_amount'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('flat_amount')" class="mt-1" />
                </div>

            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Routing</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <x-input-label for="rule_origin_mode" value="Origin" />
                    <select id="rule_origin_mode" name="origin_mode" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Without restrictions</option>
                        <option value="iata">IATA code</option>
                        <option value="country">Country</option>
                        <option value="type">Destination type</option>
                    </select>
                    <div id="origin_iata_container" class="space-y-2">
                        <input id="rule_origin_iata" name="origin_iata" maxlength="3"
                               placeholder="Origin IATA code" value="{{ $ruleFields['origin'] }}"
                               class="w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="origin_prefer_same_code" value="1" @if(!empty($ruleFields['origin_prefer_same_code'])) checked @endif
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            prefer airport with the same code
                        </label>
                    </div>
                    <div id="origin_country_container" class="space-y-2">
                        <select id="rule_origin_country" name="origin_country"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Country</option>
                            @foreach ($countryOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="origin_type_container" class="space-y-2">
                        <select id="rule_origin_type" name="origin_type"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Origin Type</option>
                            @foreach ($locationTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="origin" id="rule_origin" value="{{ $ruleFields['origin'] }}">
                    <x-input-error :messages="$errors->get('origin')" class="mt-1" />
                </div>
                <div class="space-y-2">
                    <x-input-label for="rule_destination_mode" value="Destination" />
                    <select id="rule_destination_mode" name="destination_mode" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Without restrictions</option>
                        <option value="iata">IATA code</option>
                        <option value="country">Country</option>
                        <option value="type">Destination type</option>
                    </select>
                    <div id="destination_iata_container" class="space-y-2">
                        <input id="rule_destination_iata" name="destination_iata" maxlength="3"
                               placeholder="Destination IATA code" value="{{ $ruleFields['destination'] }}"
                               class="w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="destination_prefer_same_code" value="1" @if(!empty($ruleFields['destination_prefer_same_code'])) checked @endif
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            prefer airport with the same code
                        </label>
                    </div>
                    <div id="destination_country_container" class="space-y-2">
                        <select id="rule_destination_country" name="destination_country"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Country</option>
                            @foreach ($countryOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="destination_type_container" class="space-y-2">
                        <select id="rule_destination_type" name="destination_type"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Destination Type</option>
                            @foreach ($locationTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="destination" id="rule_destination" value="{{ $ruleFields['destination'] }}">
                    <x-input-error :messages="$errors->get('destination')" class="mt-1" />
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="both_ways" value="1" @if($ruleFields['both_ways']) checked @endif
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Both ways
            </label>
            <x-input-error :messages="$errors->get('both_ways')" class="mt-1" />
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Rule is applicable for</div>
        <div class="p-4">
            @php
                $travelTypeValue = $ruleFields['travel_type'];
                if (is_string($travelTypeValue)) {
                    // Explode only if it's a non-empty string, otherwise default to an empty array.
                    $travelType = $travelTypeValue ? explode('+', $travelTypeValue) : [];
                } else {
                    // If it's already an array (or null), use it as is.
                    $travelType = is_array($travelTypeValue) ? $travelTypeValue : [];
                }
            @endphp
            <div class="flex flex-wrap items-center gap-6 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="travel_type[]" value="OW" @if(in_array('OW', $travelType)) checked @endif class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    One way flights
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="travel_type[]" value="RT" @if(in_array('RT', $travelType)) checked @endif class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Return flights
                </label>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Cabin class</div>
        <div class="space-y-3 p-4">
            <div>
                    <x-input-label for="rule_cabin_mode" value="Contains" />
                    <select id="rule_cabin_mode" name="cabin_mode" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" @if(empty($ruleFields['cabin_mode'])) selected @endif>No restriction</option>
                    <option value="higher" @if(($ruleFields['cabin_mode'] ?? '') == 'higher') selected @endif>The one specified or higher</option>
                    <option value="exact" @if(($ruleFields['cabin_mode'] ?? '') == 'exact') selected @endif>Only the one specified</option>
                    <option value="once" @if(($ruleFields['cabin_mode'] ?? '') == 'once') selected @endif>At least once</option>
                </select>
            </div>
            <div id="cabin_class_container">
                <x-input-label for="rule_cabin_class" value="Cabin class" />
                <select id="rule_cabin_class" name="cabin_class"
                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select cabin</option>
                    @foreach ($cabinClasses as $value => $label)
                        <option value="{{ $value }}" @if($ruleFields['cabin_class'] == $value) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('cabin_class')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Fares, booking classes, and passenger type</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_booking_class_usage" value="Usage of the listed classes" />
                    <select id="rule_booking_class_usage" name="booking_class_usage"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">--- choose ---</option>
                        @foreach ($bookingClassUsageOptions as $value => $label)
                            <option value="{{ $value }}" @if($ruleFields['booking_class_usage'] == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('booking_class_usage')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_booking_class_rbd" value="Booking class (RBD)" />
                    <input id="rule_booking_class_rbd" name="booking_class_rbd" maxlength="20" value="{{ $ruleFields['booking_class_rbd'] }}"
                           placeholder="Comma-separated classes allowed"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('booking_class_rbd')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="rule_passenger_types" value="Applied only for passenger types" />
                <input id="rule_passenger_types" name="passenger_types" value="{{ is_array($ruleFields['passenger_types']) ? implode(', ', $ruleFields['passenger_types']) : $ruleFields['passenger_types'] }}"
                          placeholder="ADT, CHD, INF"
                          class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <p class="mt-1 text-xs text-gray-500">Multiple types separated by comma. Use IATA codes: ADT, CNN, INF, YTH, SRC.</p>
                <x-input-error :messages="$errors->get('passenger_types')" class="mt-1" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_fare_type" value="Fare type" />
                    <select id="rule_fare_type" name="fare_type"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($fareTypes as $value => $label)
                            <option value="{{ $value }}" @if($ruleFields['fare_type'] == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('fare_type')" class="mt-1" />
                </div>
                <div class="hidden md:block" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Time periods</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label value="Sales (ticketing) since – till" />
                    <div class="mt-1 grid grid-cols-2 gap-3">
                        <input type="datetime-local" name="sales_since" value="{{ $ruleFields['sales_since'] }}"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="datetime-local" name="sales_till" value="{{ $ruleFields['sales_till'] }}"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <x-input-error :messages="$errors->get('sales_since')" class="mt-1" />
                    <x-input-error :messages="$errors->get('sales_till')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Departures since – till" />
                    <div class="mt-1 grid grid-cols-2 gap-3">
                        <input type="datetime-local" name="departures_since" value="{{ $ruleFields['departures_since'] }}"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="datetime-local" name="departures_till" value="{{ $ruleFields['departures_till'] }}"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <x-input-error :messages="$errors->get('departures_since')" class="mt-1" />
                    <x-input-error :messages="$errors->get('departures_till')" class="mt-1" />
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label value="Returns since – till" />
                    <div class="mt-1 grid grid-cols-2 gap-3">
                        <input type="datetime-local" name="returns_since" value="{{ $ruleFields['returns_since'] }}"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="datetime-local" name="returns_till" value="{{ $ruleFields['returns_till'] }}"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <x-input-error :messages="$errors->get('returns_since')" class="mt-1" />
                    <x-input-error :messages="$errors->get('returns_till')" class="mt-1" />
                </div>
                <div class="flex items-center text-xs text-gray-500 md:justify-end">dd/mm/yyyy format (local time)</div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Used with Multi PCC</div>
        <div class="p-4">
            <div class="flex flex-wrap items-center gap-6 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="is_primary_pcc" value="1" @if($ruleFields['is_primary_pcc'] == '1') checked @endif
                           class="text-indigo-600 focus:ring-indigo-500">
                    Primary
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="is_primary_pcc" value="0" @if($ruleFields['is_primary_pcc'] == '0') checked @endif
                           class="text-indigo-600 focus:ring-indigo-500">
                    Not primary
                </label>
            </div>
            <x-input-error :messages="$errors->get('is_primary_pcc')" class="mt-1" />
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Carrier Dropdown ---
        const carrierSelect = document.getElementById('rule_carrier');
        if (carrierSelect) {
            carrierSelect.value = "{{ $ruleFields['carrier'] }}";
        }

        function setupToggle(ruleId, containerId, inputId, triggerValues = null) {
            const rule = document.getElementById(ruleId);
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);

            function toggle() {
                if (!rule || !container) return;
                const val = rule.value;
                const shouldShow = triggerValues ? triggerValues.includes(val) : val !== '';

                if (shouldShow) {
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                    if (input) input.value = '';
                }
            }

            if (rule) {
                toggle();
                rule.addEventListener('change', toggle);
            }
        }

        setupToggle('rule_cabin_mode', 'cabin_class_container', 'rule_cabin_class');
        // Show operating carrier codes input only when a restriction is selected
        setupToggle('rule_operating_carriers_rule', 'operating_carriers_container', 'rule_operating_carriers', ['S','N','A']);

        function setupRoutingToggle(type) {
            const modeSelect = document.getElementById(`rule_${type}_mode`);
            const iataContainer = document.getElementById(`${type}_iata_container`);
            const countryContainer = document.getElementById(`${type}_country_container`);
            const typeContainer = document.getElementById(`${type}_type_container`);
            const valueInput = document.getElementById(`rule_${type}`);
            const iataInput = document.getElementById(`rule_${type}_iata`);

            function toggle() {
                if (!modeSelect) return;

                const mode = modeSelect.value;
                
                if(iataContainer) iataContainer.style.display = 'none';
                if(countryContainer) countryContainer.style.display = 'none';
                if(typeContainer) typeContainer.style.display = 'none';

                if (valueInput) valueInput.value = '';

                switch (mode) {
                    case 'iata':
                        if(iataContainer) iataContainer.style.display = 'block';
                        if (valueInput && iataInput) valueInput.value = iataInput.value;
                        break;
                    case 'country':
                        if(countryContainer) countryContainer.style.display = 'block';
                        break;
                    case 'type':
                        if(typeContainer) typeContainer.style.display = 'block';
                        break;
                }
            }

            if (modeSelect) {
                // Set initial state
                const initialValue = valueInput ? valueInput.value : '';
                if (initialValue) {
                    modeSelect.value = 'iata';
                } else {
                    // This could be improved if we stored country/type selections
                    modeSelect.value = ''; 
                }
                
                toggle();
                modeSelect.addEventListener('change', toggle);

                if (iataInput) {
                    iataInput.addEventListener('input', function() {
                        if (modeSelect.value === 'iata') {
                           if(valueInput) valueInput.value = iataInput.value;
                        }
                    });
                }
            }
        }

        setupRoutingToggle('origin');
        setupRoutingToggle('destination');

        // Flight numbers dynamic rows
        const flightRowsContainer = document.getElementById('flight_rows');
        const addFlightBtn = document.getElementById('add_flight_row');
        const flightHidden = document.getElementById('rule_flight_numbers');

        function serializeFlights() {
            if (!flightRowsContainer || !flightHidden) return;
            const rows = flightRowsContainer.querySelectorAll('.flight-row');
            const parts = [];
            rows.forEach(row => {
                const carrier = row.querySelector('.flight-carrier')?.value.trim().toUpperCase() || '';
                const from = row.querySelector('.flight-from')?.value.trim() || '';
                const to = row.querySelector('.flight-to')?.value.trim() || '';
                if (carrier && (from || to)) {
                    parts.push(`${carrier}:${from}-${to}`);
                }
            });
            flightHidden.value = parts.join('|');
        }

        function addFlightRow(carrier = '', from = '', to = '') {
            if (!flightRowsContainer) return;
            const row = document.createElement('div');
            row.className = 'flight-row flex flex-wrap items-center gap-2';
            row.innerHTML = `
                <label class="text-sm text-gray-700">Carrier:</label>
                <input type="text" maxlength="5" class="flight-carrier w-24 rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="${carrier}">
                <span class="text-sm text-gray-700">with numbers from:</span>
                <input type="text" class="flight-from w-24 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="${from}">
                <span class="text-sm text-gray-700">to:</span>
                <input type="text" class="flight-to w-24 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="${to}">
                <button type="button" class="remove-flight text-rose-600 text-lg leading-none">&times;</button>
            `;
            row.querySelectorAll('input').forEach(input => input.addEventListener('input', serializeFlights));
            const removeBtn = row.querySelector('.remove-flight');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    row.remove();
                    serializeFlights();
                });
            }
            flightRowsContainer.appendChild(row);
        }

        if (addFlightBtn) {
            addFlightBtn.addEventListener('click', () => {
                addFlightRow();
            });
        }
        // Seed existing flight numbers from hidden input
        if (flightHidden && flightHidden.value) {
            const entries = flightHidden.value.split('|');
            entries.forEach(entry => {
                const [left, range] = entry.split(':');
                const [from, to] = (range || '').split('-');
                if (left) {
                    addFlightRow(left, from || '', to || '');
                }
            });
        }

        // Commission/Discount amount mode toggle
        const amountModeSelect = document.getElementById('rule_amount_mode');
        const percentContainer = document.getElementById('percent_container');
        const flatContainer = document.getElementById('flat_container');
        function toggleAmountMode() {
            if (!amountModeSelect) return;
            const mode = amountModeSelect.value;
            if (percentContainer) percentContainer.style.display = mode === 'pct' ? 'block' : 'none';
            if (flatContainer) flatContainer.style.display = mode === 'abs' ? 'block' : 'none';
            if (mode === 'pct' && flatContainer) {
                const flatInput = flatContainer.querySelector('input');
                if (flatInput) flatInput.value = '';
            }
            if (mode === 'abs' && percentContainer) {
                const pctInput = percentContainer.querySelector('input');
                if (pctInput) pctInput.value = '';
            }
        }
        if (amountModeSelect) {
            toggleAmountMode();
            amountModeSelect.addEventListener('change', toggleAmountMode);
        }

    });
</script>
