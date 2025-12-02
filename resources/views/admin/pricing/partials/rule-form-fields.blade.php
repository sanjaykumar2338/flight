<div class="space-y-5">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Edit rule</div>
        <div class="space-y-4 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_priority" value="Priority" />
                    <input id="rule_priority" name="priority" type="number" min="0" max="1000" x-model="$store.pricingRules.form.priority"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('priority')" class="mt-1" />
                </div>
                <div class="hidden md:block" aria-hidden="true"></div>
                <div class="md:col-span-2">
                    <x-input-label value="Usage" />
                    <div class="mt-2 grid gap-2 rounded border border-gray-200 bg-white p-3">
                        @foreach ($creationUsageOptions as $value => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="usage" value="{{ $value }}" x-model="$store.pricingRules.form.usage"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('usage')" class="mt-1" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="rule_notes" value="Notes" />
                    <textarea id="rule_notes" name="notes" rows="3" x-model="$store.pricingRules.form.notes"
                              class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
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
                    <select id="rule_carrier" name="carrier" x-model="$store.pricingRules.form.carrier"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All carriers (no restriction)</option>
                        @include('admin.pricing.partials.carrier-options')
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Choose a specific carrier or leave as “All carriers”.</p>
                    <x-input-error :messages="$errors->get('carrier')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_plating_carrier" value="Plating carrier" />
                    <input id="rule_plating_carrier" name="plating_carrier" maxlength="5" x-model="$store.pricingRules.form.plating_carrier"
                           x-on:input="$store.pricingRules.form.plating_carrier = $event.target.value.toUpperCase()"
                           placeholder="Without restrictions"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('plating_carrier')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_marketing_carriers_rule" value="Marketing carriers" />
                    <select id="rule_marketing_carriers_rule" name="marketing_carriers_rule" x-model="$store.pricingRules.form.marketing_carriers_rule"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($marketingRuleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('marketing_carriers_rule')" class="mt-1" />
                </div>
                <div class="md:col-span-2" x-show="$store.pricingRules.form.marketing_carriers_rule !== ''" x-cloak>
                    <x-input-label for="rule_marketing_carriers" value="Carrier IATA code(s)" />
                    <input id="rule_marketing_carriers" name="marketing_carriers" x-model="$store.pricingRules.marketingCarriersText"
                           @input="$store.pricingRules.updateCarrierListFromText('marketing', $event.target.value)"
                           placeholder="Multiple codes separated by comma"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">Applies when marketing carriers are limited to or excluding a list.</p>
                    <x-input-error :messages="$errors->get('marketing_carriers')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_operating_carriers_rule" value="Operating carriers" />
                    <select id="rule_operating_carriers_rule" name="operating_carriers_rule" x-model="$store.pricingRules.form.operating_carriers_rule"
                            @change="$store.pricingRules.onOperatingCarrierRuleChange()"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($operatingRuleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('operating_carriers_rule')" class="mt-1" />
                </div>
                <div class="md:col-span-2" x-show="$store.pricingRules.form.operating_carriers_rule !== ''" x-cloak>
                    <x-input-label for="rule_operating_carriers" value="Carrier IATA code(s)" />
                    <input id="rule_operating_carriers" name="operating_carriers" x-model="$store.pricingRules.operatingCarriersText"
                           @input="$store.pricingRules.updateCarrierListFromText('operating', $event.target.value)"
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
                    <select id="rule_flight_restriction_type" name="flight_restriction_type" x-model="$store.pricingRules.form.flight_restriction_type"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($flightRestrictionOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('flight_restriction_type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_flight_numbers" value="Flight numbers" />
                    <input id="rule_flight_numbers" name="flight_numbers" x-model="$store.pricingRules.form.flight_numbers"
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
                <div>
                    <x-input-label for="rule_amount_mode" value="Commission / Discount" />
                    <select id="rule_amount_mode" x-model="$store.pricingRules.form.amount_mode" @change="$store.pricingRules.syncAmountMode()"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="percent">Percent</option>
                        <option value="flat">Fixed</option>
                    </select>
                </div>
                <div>
                    <div x-show="$store.pricingRules.form.amount_mode === 'percent'" x-cloak>
                        <x-input-label for="rule_percent" value="Percent" />
                        <input id="rule_percent" name="percent" type="number" step="0.0001" min="0" max="100" x-model="$store.pricingRules.form.percent"
                               class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <x-input-error :messages="$errors->get('percent')" class="mt-1" />
                    </div>
                    <div x-show="$store.pricingRules.form.amount_mode === 'flat'" x-cloak>
                        <x-input-label for="rule_flat_amount" value="Fixed amount" />
                        <input id="rule_flat_amount" name="flat_amount" type="number" step="0.01" min="0" x-model="$store.pricingRules.form.flat_amount"
                               class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <x-input-error :messages="$errors->get('flat_amount')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <x-input-label for="rule_fee_percent" value="Fee percent (optional)" />
                    <input id="rule_fee_percent" name="fee_percent" type="number" step="0.0001" min="0" max="100" x-model="$store.pricingRules.form.fee_percent"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('fee_percent')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_fixed_fee" value="Fixed fee" />
                    <input id="rule_fixed_fee" name="fixed_fee" type="number" step="0.01" min="0" x-model="$store.pricingRules.form.fixed_fee"
                           class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('fixed_fee')" class="mt-1" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="rule_promo_code" value="Promo code" />
                    <input id="rule_promo_code" name="promo_code" maxlength="32" x-model="$store.pricingRules.form.promo_code"
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
                    <x-input-label for="rule_origin_mode" value="Origin" />
                    <select id="rule_origin_mode" name="origin_mode" x-model="$store.pricingRules.form.origin_mode"
                            @change="$store.pricingRules.onOriginModeChange()"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Without restrictions</option>
                        <option value="iata">IATA code</option>
                        <option value="country">Country</option>
                        <option value="type">Destination type</option>
                    </select>
                    <div class="space-y-2" x-show="$store.pricingRules.form.origin_mode === 'iata'" x-cloak>
                        <input id="rule_origin" name="origin_iata" maxlength="3"
                               x-model="$store.pricingRules.form.origin_iata"
                               @input="$store.pricingRules.updateOriginIata($event.target.value)"
                               placeholder="Origin IATA code"
                               class="w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="origin_prefer_same_code" value="1"
                                   x-model="$store.pricingRules.form.origin_prefer_same_code"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Prefer airport with same code
                        </label>
                    </div>
                    <div class="space-y-2" x-show="$store.pricingRules.form.origin_mode === 'country'" x-cloak>
                        <select id="rule_origin_country" name="origin_country"
                                x-model="$store.pricingRules.form.origin_country"
                                @change="$store.pricingRules.updateOriginCountry($event.target.value)"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Country</option>
                            @foreach ($countryOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2" x-show="$store.pricingRules.form.origin_mode === 'type'" x-cloak>
                        <select id="rule_origin_type" name="origin_type"
                                x-model="$store.pricingRules.form.origin_type"
                                @change="$store.pricingRules.updateOriginType($event.target.value)"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Origin Type</option>
                            @foreach ($locationTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="origin" x-model="$store.pricingRules.form.origin">
                    <x-input-error :messages="$errors->get('origin')" class="mt-1" />
                </div>
                <div class="space-y-2">
                    <x-input-label for="rule_destination_mode" value="Destination" />
                    <select id="rule_destination_mode" name="destination_mode" x-model="$store.pricingRules.form.destination_mode"
                            @change="$store.pricingRules.onDestinationModeChange()"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Without restrictions</option>
                        <option value="iata">IATA code</option>
                        <option value="country">Country</option>
                        <option value="type">Destination type</option>
                    </select>
                    <div class="space-y-2" x-show="$store.pricingRules.form.destination_mode === 'iata'" x-cloak>
                        <input id="rule_destination" name="destination_iata" maxlength="3"
                               x-model="$store.pricingRules.form.destination_iata"
                               @input="$store.pricingRules.updateDestinationIata($event.target.value)"
                               placeholder="Destination IATA code"
                               class="w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="destination_prefer_same_code" value="1"
                                   x-model="$store.pricingRules.form.destination_prefer_same_code"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Prefer airport with same code
                        </label>
                    </div>
                    <div class="space-y-2" x-show="$store.pricingRules.form.destination_mode === 'country'" x-cloak>
                        <select id="rule_destination_country" name="destination_country"
                                x-model="$store.pricingRules.form.destination_country"
                                @change="$store.pricingRules.updateDestinationCountry($event.target.value)"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Country</option>
                            @foreach ($countryOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2" x-show="$store.pricingRules.form.destination_mode === 'type'" x-cloak>
                        <select id="rule_destination_type" name="destination_type"
                                x-model="$store.pricingRules.form.destination_type"
                                @change="$store.pricingRules.updateDestinationType($event.target.value)"
                                class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Destination Type</option>
                            @foreach ($locationTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="destination" x-model="$store.pricingRules.form.destination">
                    <x-input-error :messages="$errors->get('destination')" class="mt-1" />
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="both_ways" value="1" x-model="$store.pricingRules.form.both_ways"
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Both ways
            </label>
            <x-input-error :messages="$errors->get('both_ways')" class="mt-1" />
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Rule is applicable for</div>
        <div class="p-4">
            <div class="flex flex-wrap items-center gap-6 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           x-model="$store.pricingRules.form.travel_oneway"
                           @change="$store.pricingRules.updateTravelType()">
                    One way flights
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           x-model="$store.pricingRules.form.travel_return"
                           @change="$store.pricingRules.updateTravelType()">
                    Return flights
                </label>
            </div>
            <input type="hidden" name="travel_type" x-model="$store.pricingRules.form.travel_type">
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700">Cabin class</div>
        <div class="space-y-3 p-4">
            <div>
                <x-input-label for="rule_cabin_mode" value="Contains" />
                <select id="rule_cabin_mode" x-model="$store.pricingRules.form.cabin_mode" @change="$store.pricingRules.onCabinModeChange()"
                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No restriction</option>
                    <option value="higher">The one specified or higher</option>
                    <option value="exact">Only the one specified</option>
                    <option value="once">At least once</option>
                </select>
            </div>
            <div x-show="$store.pricingRules.form.cabin_mode !== ''" x-cloak>
                <x-input-label for="rule_cabin_class" value="Cabin class" />
                <select id="rule_cabin_class" name="cabin_class" x-model="$store.pricingRules.form.cabin_class"
                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select cabin</option>
                    @foreach ($cabinClasses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
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
                    <select id="rule_booking_class_usage" name="booking_class_usage" x-model="$store.pricingRules.form.booking_class_usage"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">--- choose ---</option>
                        @foreach ($bookingClassUsageOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('booking_class_usage')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rule_booking_class_rbd" value="Booking class (RBD)" />
                    <input id="rule_booking_class_rbd" name="booking_class_rbd" maxlength="20" x-model="$store.pricingRules.form.booking_class_rbd"
                           placeholder="Comma-separated classes allowed"
                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <x-input-error :messages="$errors->get('booking_class_rbd')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="rule_passenger_types" value="Applied only for passenger types" />
                <textarea id="rule_passenger_types" rows="2"
                          x-model="$store.pricingRules.passengerTypesText"
                          @input="$store.pricingRules.updatePassengerTypesFromText($event.target.value)"
                          placeholder="ADT, CHD, INF"
                          class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <p class="mt-1 text-xs text-gray-500">Multiple types separated by comma. Use IATA codes: ADT, CNN, INF, YTH, SRC.</p>
                <template x-for="(type, index) in $store.pricingRules.form.passenger_types" :key="`${type}-${index}`">
                    <input type="hidden" name="passenger_types[]" :value="type">
                </template>
                <x-input-error :messages="$errors->get('passenger_types')" class="mt-1" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="rule_fare_type" value="Fare type" />
                    <select id="rule_fare_type" name="fare_type" x-model="$store.pricingRules.form.fare_type"
                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($fareTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
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
                        <input type="datetime-local" name="sales_since" x-model="$store.pricingRules.form.sales_since"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="datetime-local" name="sales_till" x-model="$store.pricingRules.form.sales_till"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <x-input-error :messages="$errors->get('sales_since')" class="mt-1" />
                    <x-input-error :messages="$errors->get('sales_till')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Departures since – till" />
                    <div class="mt-1 grid grid-cols-2 gap-3">
                        <input type="datetime-local" name="departures_since" x-model="$store.pricingRules.form.departures_since"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="datetime-local" name="departures_till" x-model="$store.pricingRules.form.departures_till"
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
                        <input type="datetime-local" name="returns_since" x-model="$store.pricingRules.form.returns_since"
                               class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="datetime-local" name="returns_till" x-model="$store.pricingRules.form.returns_till"
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
                    <input type="radio" name="is_primary_pcc" value="1" x-model="$store.pricingRules.form.is_primary_pcc"
                           class="text-indigo-600 focus:ring-indigo-500">
                    Primary
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="is_primary_pcc" value="0" x-model="$store.pricingRules.form.is_primary_pcc"
                           class="text-indigo-600 focus:ring-indigo-500">
                    Not primary
                </label>
            </div>
            <x-input-error :messages="$errors->get('is_primary_pcc')" class="mt-1" />
        </div>
    </div>
</div>
