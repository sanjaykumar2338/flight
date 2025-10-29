@php
    $usageOptions = $options['usage_options'] ?? [];
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
                        <a href="{{ route('admin.airline-commissions.index') }}"
                           class="inline-flex items-center rounded border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">
                            Legacy commissions
                        </a>
                        <form method="POST" action="{{ route('admin.pricing.import-legacy') }}">
                            @csrf
                            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                            <button type="submit" class="inline-flex items-center rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-100">
                                Import legacy commissions
                            </button>
                        </form>
                        <button
                            type="button"
                            class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                            @click="openCreate()"
                        >
                            Add a rule
                        </button>
                    </div>
                </div>

                <div class="mt-6 border-b border-gray-200">
                    <nav class="-mb-px flex gap-6 text-sm font-medium">
                        <a href="{{ route('admin.pricing.index', array_merge(request()->except('page'), ['tab' => 'rules'])) }}"
                           class="px-1 pb-3 {{ $tab === 'rules' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                            Rules
                        </a>
                        <a href="{{ route('admin.pricing.index', array_merge(request()->except('page'), ['tab' => 'audit'])) }}"
                           class="px-1 pb-3 {{ $tab === 'audit' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                            Audit
                        </a>
                    </nav>
                </div>

                <div class="mt-6">
                    @if (session('status'))
                        <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($tab === 'audit')
                        <div class="rounded border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-sm text-gray-600">
                            Audit timeline coming soon. We will track CRUD events once the primary rules workflow is verified.
                        </div>
                    @else
                        <div class="grid gap-6 lg:grid-cols-[320px,1fr]">
                            <form method="GET" action="{{ route('admin.pricing.index') }}" class="space-y-4">
                                <input type="hidden" name="tab" value="rules">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700">Filters</h3>
                                    <p class="text-xs text-gray-500">Refine rules by carrier, usage, booking class, and travel attributes.</p>
                                </div>

                                <div>
                                    <x-input-label for="filter_carrier" value="Carrier" />
                                    <input id="filter_carrier" type="text" name="carrier" maxlength="3"
                                           value="{{ $filters['carrier'] }}"
                                           list="pricing-carrier-options"
                                           class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <datalist id="pricing-carrier-options">
                                        @foreach ($carrierOptions as $carrier)
                                            <option value="{{ $carrier }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>

                                <div>
                                    <x-input-label for="filter_usage" value="Usage" />
                                    <select id="filter_usage" name="usage" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">--- choose ---</option>
                                        @foreach ($usageOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($filters['usage'] === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label for="filter_origin" value="Origin" />
                                        <input id="filter_origin" type="text" name="origin" maxlength="3"
                                               value="{{ $filters['origin'] }}" class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <x-input-label for="filter_destination" value="Destination" />
                                        <input id="filter_destination" type="text" name="destination" maxlength="3"
                                               value="{{ $filters['destination'] }}" class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label for="filter_both_ways" value="Both ways" />
                                        <select id="filter_both_ways" name="both_ways" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">--- choose ---</option>
                                            <option value="1" @selected($filters['both_ways'] === true)>Yes</option>
                                            <option value="0" @selected($filters['both_ways'] === false)>No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="filter_travel_type" value="Travel type" />
                                        <select id="filter_travel_type" name="travel_type" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">--- choose ---</option>
                                            @foreach ($travelTypes as $value => $label)
                                                <option value="{{ $value }}" @selected($filters['travel_type'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label for="filter_cabin_class" value="Cabin class" />
                                        <select id="filter_cabin_class" name="cabin_class" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">--- choose ---</option>
                                            @foreach ($cabinClasses as $value => $label)
                                                <option value="{{ $value }}" @selected($filters['cabin_class'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="filter_fare_type" value="Fare type" />
                                        <select id="filter_fare_type" name="fare_type" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">--- choose ---</option>
                                            @foreach ($fareTypes as $value => $label)
                                                <option value="{{ $value }}" @selected($filters['fare_type'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label for="filter_booking_class_rbd" value="Booking class (RBD)" />
                                        <input id="filter_booking_class_rbd" type="text" name="booking_class_rbd" maxlength="10"
                                               value="{{ $filters['booking_class_rbd'] }}" class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <x-input-label for="filter_booking_class_usage" value="Usage of booking classes" />
                                        <select id="filter_booking_class_usage" name="booking_class_usage" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">--- choose ---</option>
                                            @foreach ($bookingClassUsageOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($filters['booking_class_usage'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="filter_passenger_types" value="Passenger types" />
                                    <select id="filter_passenger_types" name="passenger_types[]" multiple size="4"
                                            class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach (array_unique($passengerTypeOptions) as $type)
                                            <option value="{{ $type }}" @selected(in_array($type, $filters['passenger_types'] ?? [], true))>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Hold Cmd/Ctrl to select multiple types.</p>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <x-input-label value="Sales window" />
                                        <div class="mt-1 grid grid-cols-2 gap-3">
                                            <input type="date" name="sales_since" value="{{ $filters['sales_range']['since'] }}" class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <input type="date" name="sales_till" value="{{ $filters['sales_range']['till'] }}" class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                    </div>
                                    <div>
                                        <x-input-label value="Departures window" />
                                        <div class="mt-1 grid grid-cols-2 gap-3">
                                            <input type="date" name="departures_since" value="{{ $filters['departures_range']['since'] }}" class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <input type="date" name="departures_till" value="{{ $filters['departures_range']['till'] }}" class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                    </div>
                                    <div>
                                        <x-input-label value="Returns window" />
                                        <div class="mt-1 grid grid-cols-2 gap-3">
                                            <input type="date" name="returns_since" value="{{ $filters['returns_range']['since'] }}" class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <input type="date" name="returns_till" value="{{ $filters['returns_range']['till'] }}" class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="filter_promo_code" value="Promo code" />
                                    <input id="filter_promo_code" type="text" name="promo_code" maxlength="32"
                                           value="{{ $filters['promo_code'] }}" class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>

                                <div>
                                    <x-input-label for="filter_active" value="Active" />
                                    <select id="filter_active" name="active" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">--- choose ---</option>
                                        <option value="1" @selected($filters['active'] === true)>Active</option>
                                        <option value="0" @selected($filters['active'] === false)>Inactive</option>
                                    </select>
                                </div>

                                <div class="flex gap-3">
                                    <x-primary-button>{{ __('Apply filters') }}</x-primary-button>
                                    <a href="{{ route('admin.pricing.index', ['tab' => 'rules']) }}" class="text-sm font-semibold text-gray-600 hover:text-gray-800">
                                        Remove filter
                                    </a>
                                </div>
                            </form>

                            <div class="overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Id</th>
                                                <th class="px-3 py-2 text-left">Priority</th>
                                                <th class="px-3 py-2 text-left">Carrier</th>
                                                <th class="px-3 py-2 text-left">Usage</th>
                                                <th class="px-3 py-2 text-left">% / Flat / Fee</th>
                                                <th class="px-3 py-2 text-left">Origin</th>
                                                <th class="px-3 py-2 text-left">Destination</th>
                                                <th class="px-3 py-2 text-left">Both ways</th>
                                                <th class="px-3 py-2 text-left">Travel</th>
                                                <th class="px-3 py-2 text-left">Booking class</th>
                                                <th class="px-3 py-2 text-left">Cabin</th>
                                                <th class="px-3 py-2 text-left">Fare type</th>
                                                <th class="px-3 py-2 text-left">Sales window</th>
                                                <th class="px-3 py-2 text-left">Departures</th>
                                                <th class="px-3 py-2 text-left">Promo</th>
                                                <th class="px-3 py-2 text-left">Active</th>
                                                <th class="px-3 py-2 text-left">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @forelse ($rules as $rule)
                                                @php
                                                    $ruleData = [
                                                        'id' => $rule->id,
                                                        'priority' => $rule->priority,
                                                        'carrier' => $rule->carrier,
                                                        'usage' => $rule->usage,
                                                        'usage_label' => $usageOptions[$rule->usage] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $rule->usage ?? '')),
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
                                                <tr class="hover:bg-indigo-50/40">
                                                    <td class="px-3 py-2 font-medium text-gray-800">#{{ $rule->id }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $rule->priority }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $rule->carrier ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-700">
                                                        {{ $usageOptions[$rule->usage] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $rule->usage ?? '')) }}
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-700">
                                                        <div class="flex flex-col">
                                                            <span>{{ $rule->percent !== null ? number_format((float) $rule->percent, 2).'%' : '—' }}</span>
                                                            <span>{{ $rule->flat_amount !== null ? number_format((float) $rule->flat_amount, 2) : '—' }}</span>
                                                            <span>{{ $rule->fee_percent !== null || $rule->fixed_fee !== null ? sprintf('%s / %s',
                                                                $rule->fee_percent !== null ? number_format((float) $rule->fee_percent, 2).'%' : '—',
                                                                $rule->fixed_fee !== null ? number_format((float) $rule->fixed_fee, 2) : '—'
                                                            ) : '—' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $rule->origin ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $rule->destination ?? '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $rule->both_ways ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ $rule->both_ways ? 'Yes' : 'No' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $travelTypes[$rule->travel_type] ?? ($rule->travel_type ?? '—') }}</td>
                                                    <td class="px-3 py-2 text-gray-700">
                                                        <div class="flex flex-col">
                                                            <span>{{ $rule->booking_class_rbd ?? '—' }}</span>
                                                            <span class="text-xs text-gray-500">{{ $bookingClassUsageOptions[$rule->booking_class_usage] ?? '—' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $rule->cabin_class ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $fareTypes[$rule->fare_type] ?? ($rule->fare_type ?? '—') }}</td>
                                                    <td class="px-3 py-2 text-gray-700">
                                                        <div class="flex flex-col text-xs">
                                                            <span>{{ $rule->sales_since?->format('Y-m-d') ?? '—' }}</span>
                                                            <span>{{ $rule->sales_till?->format('Y-m-d') ?? '—' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-700">
                                                        <div class="flex flex-col text-xs">
                                                            <span>{{ $rule->departures_since?->format('Y-m-d') ?? '—' }}</span>
                                                            <span>{{ $rule->departures_till?->format('Y-m-d') ?? '—' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $rule->promo_code ?? '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $rule->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                            {{ $rule->active ? 'Active' : 'Disabled' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-sm">
                                                        <div class="flex flex-wrap gap-3 text-indigo-600">
                                                            <button type="button" class="hover:underline" @click="openDetail(@js($ruleData))">Detail</button>
                                                            <button type="button" class="hover:underline" @click="openEdit(@js($ruleData))">Edit</button>
                                                            <button type="button" class="hover:underline" @click="openCopy(@js($ruleData))">Copy</button>
                                                            <form method="POST" action="{{ route('admin.pricing.rules.destroy', $rule) }}" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                                                <button type="submit" class="text-rose-600 hover:underline" onclick="return confirm('Delete pricing rule #{{ $rule->id }}?')">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="15" class="px-3 py-6 text-center text-sm text-gray-500">
                                                        No pricing rules found. Adjust filters or add a new rule.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="border-t border-gray-200 bg-gray-50 px-3 py-2">
                                    {{ $rules->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
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
                                <x-input-label for="rule_carrier" value="Carrier" />
                                <input id="rule_carrier" name="carrier" maxlength="3" x-model="$store.pricingRules.form.carrier"
                                       class="mt-1 w-full rounded border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="rule_usage" value="Usage" />
                                <select id="rule_usage" name="usage" x-model="$store.pricingRules.form.usage"
                                        class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($usageOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
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
                                <dd class="col-span-2 font-semibold" x-text="$store.pricingRules.detail.carrier || '—'"></dd>
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
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                            },
                            openCopy(rule = {}) {
                                this.mode = 'create';
                                const data = Object.assign(defaults(), rule);
                                data.id = null;
                                this.form = data;
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                            },
                            openDetail(rule = {}) {
                                this.detail = Object.assign(defaults(), rule);
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
