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
    $passengerTypeOptions = array_merge(['ADT', 'CHD', 'INF'], $options['passenger_types'] ?? []);
    $carrierOptions = $options['carriers'] ?? [];
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
                        <button
                            type="button"
                            class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                            @click="openCreate()"
                        >
                            Add a rule
                        </button>
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
                                                'all_carriers' => empty($rule->carrier),
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
                                                <button type="button"
                                                    class="text-sm font-semibold text-indigo-600 hover:underline"
                                                    @click="openEdit(@js($ruleData))">
                                                    Edit
                                                </button>
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
                    <form method="POST" :action="$store.pricingRules.formAction()" class="space-y-4" x-ref="ruleForm">
                        @csrf
                        <template x-if="$store.pricingRules.mode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="return_url" :value="$store.pricingRules.config.returnUrl">

                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800" x-text="$store.pricingRules.modeTitle()"></h2>
                                <p class="text-sm text-gray-500">Set the scope and action for this pricing adjustment.</p>
                            </div>
                            <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="$dispatch('close-modal', 'pricing-rule-modal')">
                                Close
                            </button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="rule_priority" value="Priority" />
                                <input id="rule_priority" name="priority" type="number" min="0" max="1000" x-model="$store.pricingRules.form.priority"
                                       class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <x-input-label for="rule_carrier" value="Carrier (leave blank for all carriers)" />
                                <div class="flex flex-wrap items-center gap-3">
                                    <input id="rule_carrier" name="carrier" maxlength="3" x-model="$store.pricingRules.form.carrier"
                                           :disabled="$store.pricingRules.form.all_carriers"
                                           x-on:input="$store.pricingRules.form.carrier = $event.target.value.toUpperCase()"
                                           placeholder="e.g. EK"
                                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500" />
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               x-model="$store.pricingRules.form.all_carriers"
                                               @change="if ($event.target.checked) { $store.pricingRules.form.carrier = ''; }">
                                        Apply to all carriers
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Turn off “all carriers” to target a specific airline code.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="rule_usage" value="Usage" />
                                <select id="rule_usage" name="usage" x-model="$store.pricingRules.form.usage"
                                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($creationUsageOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Only simple commission rules can be created from this screen.</p>
                            </div>
                            <div>
                                <x-input-label for="rule_fare_type" value="Fare type" />
                                <select id="rule_fare_type" name="fare_type" x-model="$store.pricingRules.form.fare_type"
                                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">--- choose ---</option>
                                    @foreach ($fareTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="rule_origin" value="Origin (optional)" />
                                <input id="rule_origin" name="origin" maxlength="3" x-model="$store.pricingRules.form.origin"
                                       class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <x-input-label for="rule_destination" value="Destination (optional)" />
                                <input id="rule_destination" name="destination" maxlength="3" x-model="$store.pricingRules.form.destination"
                                       class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="both_ways" value="1" x-model="$store.pricingRules.form.both_ways"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Both ways
                            </label>

                            <div>
                                <x-input-label for="rule_travel_type" value="Travel type" />
                                <select id="rule_travel_type" name="travel_type" x-model="$store.pricingRules.form.travel_type"
                                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">--- choose ---</option>
                                    @foreach ($travelTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="rule_cabin_class" value="Cabin" />
                                <select id="rule_cabin_class" name="cabin_class" x-model="$store.pricingRules.form.cabin_class"
                                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">--- choose ---</option>
                                    @foreach ($cabinClasses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="rule_booking_class_rbd" value="Booking class (RBD)" />
                                <input id="rule_booking_class_rbd" name="booking_class_rbd" maxlength="10" x-model="$store.pricingRules.form.booking_class_rbd"
                                       class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <x-input-label for="rule_booking_class_usage" value="Usage of booking classes" />
                                <select id="rule_booking_class_usage" name="booking_class_usage" x-model="$store.pricingRules.form.booking_class_usage"
                                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">--- choose ---</option>
                                    @foreach ($bookingClassUsageOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="rule_passenger_types" value="Passenger types" />
                            <select id="rule_passenger_types" name="passenger_types[]" multiple size="3"
                                    x-model="$store.pricingRules.form.passenger_types"
                                    class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (array_unique($passengerTypeOptions) as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label value="Sales window" />
                                <div class="mt-1 grid grid-cols-2 gap-3">
                                    <input type="datetime-local" name="sales_since" x-model="$store.pricingRules.form.sales_since"
                                           class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <input type="datetime-local" name="sales_till" x-model="$store.pricingRules.form.sales_till"
                                           class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>
                            </div>
                            <div>
                                <x-input-label value="Departures window" />
                                <div class="mt-1 grid grid-cols-2 gap-3">
                                    <input type="datetime-local" name="departures_since" x-model="$store.pricingRules.form.departures_since"
                                           class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <input type="datetime-local" name="departures_till" x-model="$store.pricingRules.form.departures_till"
                                           class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label value="Returns window" />
                                <div class="mt-1 grid grid-cols-2 gap-3">
                                    <input type="datetime-local" name="returns_since" x-model="$store.pricingRules.form.returns_since"
                                           class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <input type="datetime-local" name="returns_till" x-model="$store.pricingRules.form.returns_till"
                                           class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="rule_fare_type" value="Fare type" />
                                    <select id="rule_fare_type" name="fare_type" x-model="$store.pricingRules.form.fare_type"
                                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($fareTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="rule_promo_code" value="Promo code" />
                                    <input id="rule_promo_code" name="promo_code" maxlength="32" x-model="$store.pricingRules.form.promo_code"
                                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="rule_percent" value="Percent" />
                                <input id="rule_percent" name="percent" type="number" step="0.0001" min="0" max="100" x-model="$store.pricingRules.form.percent"
                                       class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <x-input-label for="rule_flat_amount" value="Flat amount" />
                                <input id="rule_flat_amount" name="flat_amount" type="number" step="0.01" min="0" x-model="$store.pricingRules.form.flat_amount"
                                       class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <x-input-label for="rule_fee_percent" value="Fee percent" />
                                <input id="rule_fee_percent" name="fee_percent" type="number" step="0.0001" min="0" max="100" x-model="$store.pricingRules.form.fee_percent"
                                       class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <x-input-label for="rule_fixed_fee" value="Fixed fee" />
                                <input id="rule_fixed_fee" name="fixed_fee" type="number" step="0.01" min="0" x-model="$store.pricingRules.form.fixed_fee"
                                       class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="rule_notes" value="Notes" />
                            <textarea id="rule_notes" name="notes" rows="3" x-model="$store.pricingRules.form.notes"
                                      class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="rule_active" type="checkbox" name="active" value="1" x-model="$store.pricingRules.form.active"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="rule_active" class="text-sm text-gray-700">Rule is active</label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" class="text-sm text-gray-600 hover:text-gray-800" @click="$dispatch('close-modal', 'pricing-rule-modal')">
                                Cancel
                            </button>
                            <x-primary-button x-text="$store.pricingRules.mode === 'edit' ? 'Update rule' : 'Create rule'"></x-primary-button>
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
                        </dl>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('pricingRulesPage', (config) => ({
                    init() {
                        const defaults = () => ({
                            id: null,
                            priority: 0,
                            carrier: '',
                            all_carriers: true,
                            usage: '{{ \App\Models\PricingRule::USAGE_COMMISSION_BASE }}',
                            origin: '',
                            destination: '',
                            both_ways: false,
                            travel_type: 'OW+RT',
                            cabin_class: '',
                            booking_class_rbd: '',
                            booking_class_usage: '{{ \App\Models\PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE }}',
                            passenger_types: [],
                            sales_since: '',
                            sales_till: '',
                            departures_since: '',
                            departures_till: '',
                            returns_since: '',
                            returns_till: '',
                            fare_type: 'public_and_private',
                            promo_code: '',
                            percent: '',
                            flat_amount: '',
                            fee_percent: '',
                            fixed_fee: '',
                            active: true,
                            notes: '',
                        });

                        Alpine.store('pricingRules', {
                            mode: 'create',
                            form: defaults(),
                            detail: defaults(),
                            config,
                            defaults,
                            resetForm() {
                                this.form = defaults();
                            },
                            openCreate() {
                                this.mode = 'create';
                                this.resetForm();
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                            },
                            openEdit(rule = {}) {
                                this.mode = 'edit';
                                this.form = Object.assign(defaults(), rule);
                                this.form.all_carriers = !this.form.carrier;
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                            },
                            openCopy(rule = {}) {
                                this.mode = 'create';
                                const data = Object.assign(defaults(), rule);
                                data.id = null;
                                data.all_carriers = !data.carrier;
                                this.form = data;
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                            },
                            openDetail(rule = {}) {
                                this.detail = Object.assign(defaults(), rule);
                                this.detail.all_carriers = !this.detail.carrier;
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-detail' }));
                            },
                            formAction() {
                                if (this.mode === 'edit' && this.form.id) {
                                    return `${this.config.updateBaseUrl}/${this.form.id}`;
                                }

                                return this.config.createUrl;
                            },
                            modeTitle() {
                                if (this.mode === 'edit' && this.form.id) {
                                    return `Edit rule #${this.form.id}`;
                                }

                                return 'Create pricing rule';
                            },
                        });
                    },
                    store() {
                        return Alpine.store('pricingRules');
                    },
                    openCreate() {
                        this.store().openCreate();
                    },
                    openEdit(rule) {
                        this.store().openEdit(rule);
                    },
                    openCopy(rule) {
                        this.store().openCopy(rule);
                    },
                    openDetail(rule) {
                        this.store().openDetail(rule);
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
