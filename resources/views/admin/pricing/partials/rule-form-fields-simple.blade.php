@php
    // This is a simplified version of the form fields, with all Alpine.js logic removed.
    // Values are populated using old() or the $ruleFields array defined in the parent view.
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
                <div class="md:col-span-2">
                    <x-input-label for="rule_notes" value="Notes" />
                    <textarea id="rule_notes" name="notes" rows="3"
                              class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $ruleFields['notes'] }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Airlines</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="rule_carrier" value="Carrier" />
                    <select id="rule_carrier" name="carrier"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All carriers (no restriction)</option>
                        @include('admin.pricing.partials.carrier-options')
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Choose a specific carrier or leave as “All carriers”.</p>
                    <x-input-error :messages="$errors->get('carrier')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_plating_carrier" value="Plating carrier" />
                    <input id="rule_plating_carrier" name="plating_carrier" maxlength="5" value="{{ $ruleFields['plating_carrier'] }}"
                           placeholder="Without restrictions"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('plating_carrier')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_marketing_carriers_rule" value="Marketing carriers" />
                    <select id="rule_marketing_carriers_rule" name="marketing_carriers_rule"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($marketingRuleOptions as $value => $label)
                            <option value="{{ $value }}" @if($ruleFields['marketing_carriers_rule'] == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('marketing_carriers_rule')" class="mt-1" />
                </div>
                <div id="marketing_carriers_container" class="md:col-span-2">
                    <x-input-label for="rule_marketing_carriers" value="Carrier IATA code(s)" />
                    <input id="rule_marketing_carriers" name="marketing_carriers" value="{{ is_array($ruleFields['marketing_carriers']) ? implode(', ', $ruleFields['marketing_carriers']) : $ruleFields['marketing_carriers'] }}"
                           placeholder="Multiple codes separated by comma"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">Applies when marketing carriers are limited to or excluding a list.</p>
                    <x-input-error :messages="$errors->get('marketing_carriers')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_operating_carriers_rule" value="Operating carriers" />
                    <select id="rule_operating_carriers_rule" name="operating_carriers_rule"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($operatingRuleOptions as $value => $label)
                            <option value="{{ $value }}" @if($ruleFields['operating_carriers_rule'] == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('operating_carriers_rule')" class="mt-1" />
                </div>
                <div id="operating_carriers_container" class="md:col-span-2">
                    <x-input-label for="rule_operating_carriers" value="Carrier IATA code(s)" />
                    <input id="rule_operating_carriers" name="operating_carriers" value="{{ is_array($ruleFields['operating_carriers']) ? implode(', ', $ruleFields['operating_carriers']) : $ruleFields['operating_carriers'] }}"
                           placeholder="Multiple codes separated by comma"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">Applies when operating carriers are limited to or excluding a list.</p>
                    <x-input-error :messages="$errors->get('operating_carriers')" class="mt-1" />
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Flight restriction</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_flight_restriction_type" value="Flight restriction" />
                    <select id="rule_flight_restriction_type" name="flight_restriction_type"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($flightRestrictionOptions as $value => $label)
                            <option value="{{ $value }}" @if($ruleFields['flight_restriction_type'] == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('flight_restriction_type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_flight_numbers" value="Flight numbers" />
                    <input id="rule_flight_numbers" name="flight_numbers" value="{{ $ruleFields['flight_numbers'] }}"
                           placeholder="Multiple flight numbers separated by comma"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">Separate multiple flight numbers with commas.</p>
                    <x-input-error :messages="$errors->get('flight_numbers')" class="mt-1" />
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Commission / Discount amount</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <p class="font-medium text-gray-800">Amount</p>
                </div>
                <div>
                    <x-input-label for="rule_percent" value="Percent" />
                    <input id="rule_percent" name="percent" type="number" step="0.0001" min="0" max="100" value="{{ $ruleFields['percent'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('percent')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_flat_amount" value="Fixed amount" />
                    <input id="rule_flat_amount" name="flat_amount" type="number" step="0.01" min="0" value="{{ $ruleFields['flat_amount'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('flat_amount')" class="mt-1" />
                </div>

                <div class="md:col-span-2">
                     <p class="font-medium text-gray-800">Fees</p>
                </div>
                <div>
                    <x-input-label for="rule_fee_percent" value="Fee percent (optional)" />
                    <input id="rule_fee_percent" name="fee_percent" type="number" step="0.0001" min="0" max="100" value="{{ $ruleFields['fee_percent'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('fee_percent')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_fixed_fee" value="Fixed fee" />
                    <input id="rule_fixed_fee" name="fixed_fee" type="number" step="0.01" min="0" value="{{ $ruleFields['fixed_fee'] }}"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('fixed_fee')" class="mt-1" />
                </div>
                <div class="md:col-span-2">
                     <p class="font-medium text-gray-800">Promo Code</p>
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="rule_promo_code" value="Promo code" />
                    <input id="rule_promo_code" name="promo_code" maxlength="32" value="{{ $ruleFields['promo_code'] }}"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">Use for discounts from total price & promo code.</p>
                    <x-input-error :messages="$errors->get('promo_code')" class="mt-1" />
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Routing</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <x-input-label for="rule_origin" value="Origin" />
                    <input id="rule_origin" name="origin" maxlength="3" value="{{ $ruleFields['origin'] }}"
                           placeholder="Origin IATA code"
                           class="w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('origin')" class="mt-1" />
                </div>
                <div class="space-y-2">
                    <x-input-label for="rule_destination" value="Destination" />
                    <input id="rule_destination" name="destination" maxlength="3" value="{{ $ruleFields['destination'] }}"
                           placeholder="Destination IATA code"
                           class="w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
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
                // This logic is simplified. The original form used JS to combine these into one field.
                // This uses an array of checkboxes which the controller will need to handle.
                $travelType = is_array($ruleFields['travel_type']) ? $ruleFields['travel_type'] : explode('+', $ruleFields['travel_type']);
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

        function setupToggle(ruleId, containerId, inputId) {
            const rule = document.getElementById(ruleId);
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);

            function toggle() {
                if (!rule || !container) return;
                
                if (rule.value === '') {
                    container.style.display = 'none';
                    if (input) input.value = '';
                } else {
                    container.style.display = 'block';
                }
            }
            
            if (rule) {
                toggle();
                rule.addEventListener('change', toggle);
            }
        }

        setupToggle('rule_operating_carriers_rule', 'operating_carriers_container', 'rule_operating_carriers');
        setupToggle('rule_marketing_carriers_rule', 'marketing_carriers_container', 'rule_marketing_carriers');
        setupToggle('rule_cabin_mode', 'cabin_class_container', 'rule_cabin_class');
    });
</script>