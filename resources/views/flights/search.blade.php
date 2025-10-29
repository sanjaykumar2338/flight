@php
    $search = $searchParams ?? [];
    $selectedAirlines = $selectedAirlines ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Flight Search') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            @if (session('ref'))
                <div class="rounded border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800">
                    Referral code active: <span class="font-semibold">{{ session('ref') }}</span>
                </div>
            @endif

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="GET" action="{{ route('flights.search') }}" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <x-input-label for="origin" value="Origin (IATA)" />
                            <x-text-input id="origin" name="origin" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                                value="{{ old('origin', $search['origin'] ?? '') }}" />
                            <x-input-error :messages="$errors->get('origin')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="destination" value="Destination (IATA)" />
                            <x-text-input id="destination" name="destination" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                                value="{{ old('destination', $search['destination'] ?? '') }}" />
                            <x-input-error :messages="$errors->get('destination')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="departure_date" value="Departure Date" />
                            <x-text-input id="departure_date" name="departure_date" type="date" class="mt-1 block w-full"
                                value="{{ old('departure_date', $search['departure_date'] ?? '') }}" />
                            <x-input-error :messages="$errors->get('departure_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="return_date" value="Return Date (optional)" />
                            <x-text-input id="return_date" name="return_date" type="date" class="mt-1 block w-full"
                                value="{{ old('return_date', $search['return_date'] ?? '') }}" />
                            <x-input-error :messages="$errors->get('return_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="cabin_class" value="Cabin Class" />
                            <select id="cabin_class" name="cabin_class" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['ECONOMY' => 'Economy', 'PREMIUM_ECONOMY' => 'Premium Economy', 'BUSINESS' => 'Business', 'FIRST' => 'First'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($search['cabin_class'] ?? 'ECONOMY') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('cabin_class')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="adults" value="Adults" />
                                <x-text-input id="adults" name="adults" type="number" min="1" max="9" class="mt-1 block w-full"
                                    value="{{ old('adults', $search['adults'] ?? 1) }}" />
                                <x-input-error :messages="$errors->get('adults')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="children" value="Children" />
                                <x-text-input id="children" name="children" type="number" min="0" max="9" class="mt-1 block w-full"
                                    value="{{ old('children', $search['children'] ?? 0) }}" />
                                <x-input-error :messages="$errors->get('children')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="infants" value="Infants" />
                                <x-text-input id="infants" name="infants" type="number" min="0" max="9" class="mt-1 block w-full"
                                    value="{{ old('infants', $search['infants'] ?? 0) }}" />
                                <x-input-error :messages="$errors->get('infants')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <x-input-label for="flexible_days" value="Flexible (+/- days)" />
                            <select id="flexible_days" name="flexible_days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ([0, 1, 2, 3] as $days)
                                    <option value="{{ $days }}" @selected(($search['flexible_days'] ?? 0) == $days)>
                                        {{ $days === 0 ? 'Exact date only' : '+/- ' . $days . ' day(s)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="selected_airlines" value="Preferred Airlines" />
                            <x-text-input id="selected_airlines" name="selected_airlines" type="text" class="mt-1 block w-full"
                                value="{{ old('selected_airlines', implode(',', $selectedAirlines)) }}" placeholder="e.g. SQ, EK" />
                            <p class="mt-1 text-xs text-gray-500">Comma separated airline codes.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex gap-2">
                            @if (!empty($selectedAirlines))
                                @foreach ($selectedAirlines as $airline)
                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">
                                        {{ $airline }}
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        <div class="flex gap-3">
                            <x-secondary-button type="reset">{{ __('Reset') }}</x-secondary-button>
                            <x-primary-button>
                                {{ __('Search Flights') }}
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>

            @if ($errorMessage)
                <div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errorMessage }}
                </div>
            @endif

            @if (!empty($pricedOffer) && $pricedBooking)
                @php
                    $pricing = $pricedOffer['pricing'] ?? [];
                    $currency = $pricedOffer['currency'] ?? config('travelndc.currency', 'USD');
                    $ndc = $pricing['ndc'] ?? [];
                    $baseFare = $ndc['base_amount'] ?? ($pricing['base_amount'] ?? 0);
                    $taxes = $ndc['tax_amount'] ?? ($pricing['tax_amount'] ?? 0);
                    $adjustments = $pricing['components']['adjustments'] ?? round(($pricing['payable_total'] ?? 0) - ($baseFare + $taxes), 2);
                    $rulesApplied = $pricing['rules_applied'] ?? [];
                    $ruleCount = is_countable($rulesApplied) ? count($rulesApplied) : 0;
                    $engineUsed = data_get($pricing, 'engine.used', false);
                    $legacySource = data_get($pricing, 'legacy.source');
                @endphp
                <div class="rounded border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-semibold">Latest Offer Pricing</p>
                                <p class="text-sm">
                                    Offer {{ $pricedOffer['offer_id'] }} ({{ $pricedOffer['owner'] }}):
                                    {{ $currency }} {{ number_format($pricing['payable_total'] ?? 0, 2) }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-emerald-800">
                                    <span class="inline-flex items-center rounded-full bg-white/70 px-2 py-1 font-semibold">
                                        {{ $engineUsed ? 'Pricing rules applied' : 'Legacy pricing' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-white/70 px-2 py-1 font-semibold">
                                        {{ $ruleCount }} {{ \Illuminate\Support\Str::plural('adjustment', $ruleCount) }}
                                    </span>
                                </div>
                            </div>
                            <div class="rounded bg-white px-3 py-2 text-sm font-semibold {{ $adjustments >= 0 ? 'text-emerald-700' : 'text-rose-600' }} shadow-sm">
                                Adjustments: {{ $adjustments >= 0 ? '+' : '' }}{{ number_format($adjustments, 2) }}
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Fare Summary</p>
                                <dl class="mt-2 space-y-2 text-sm text-gray-700">
                                    <div class="flex items-center justify-between">
                                        <dt>Base fare</dt>
                                        <dd class="font-semibold">{{ $currency }} {{ number_format($baseFare, 2) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt>Taxes</dt>
                                        <dd class="font-semibold">{{ $currency }} {{ number_format($taxes, 2) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between {{ $adjustments >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        <dt>Adjustments</dt>
                                        <dd class="font-semibold">{{ $adjustments >= 0 ? '+' : '' }}{{ number_format($adjustments, 2) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between font-bold text-indigo-700">
                                        <dt>Total payable</dt>
                                        <dd>{{ $currency }} {{ number_format($pricing['payable_total'] ?? 0, 2) }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Booking Ref</p>
                                <p class="text-lg font-semibold text-gray-800">#{{ $pricedBooking->id }}</p>
                                <p class="mt-1 text-sm text-gray-600">Status: {{ ucfirst($pricedBooking->status) }}</p>
                                <p class="mt-3 text-xs uppercase tracking-wide text-gray-500">Payment Reference</p>
                                <p class="text-sm text-gray-700">{{ $pricedBooking->payment_reference ?? '—' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Total payable: {{ $currency }} {{ number_format($pricing['payable_total'] ?? 0, 2) }}</p>
                            </div>
                        </div>

                        <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Pricing Breakdown</p>
                            @if ($ruleCount > 0)
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50 text-gray-600">
                                            <tr>
                                                <th class="px-2 py-2 text-left font-semibold">Rule</th>
                                                <th class="px-2 py-2 text-left font-semibold">Kind</th>
                                                <th class="px-2 py-2 text-left font-semibold">Basis</th>
                                                <th class="px-2 py-2 text-right font-semibold">Impact</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($rulesApplied as $rule)
                                                @php
                                                    $label = $rule['label'] ?? ($rule['id'] ? 'Rule #' . $rule['id'] : 'Adjustment');
                                                    $details = array_filter([
                                                        isset($rule['percent']) ? number_format((float) $rule['percent'], 2) . '%' : null,
                                                        isset($rule['flat_amount']) ? $currency . ' ' . number_format((float) $rule['flat_amount'], 2) : null,
                                                        isset($rule['fee_percent']) ? number_format((float) $rule['fee_percent'], 2) . '% fee' : null,
                                                        isset($rule['fixed_fee']) ? $currency . ' ' . number_format((float) $rule['fixed_fee'], 2) : null,
                                                    ]);
                                                    $impactAmount = (float) ($rule['impact_amount'] ?? 0);
                                                @endphp
                                                <tr>
                                                    <td class="px-2 py-2">
                                                        <div class="font-semibold text-gray-800">{{ $label }}</div>
                                                        @if (!empty($details))
                                                            <div class="text-[11px] text-gray-500">{{ implode(' · ', $details) }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-2 py-2 text-gray-700">{{ $rule['kind'] ?? '—' }}</td>
                                                    <td class="px-2 py-2 text-gray-700">{{ $rule['basis'] ?? '—' }}</td>
                                                    <td class="px-2 py-2 text-right font-semibold {{ $impactAmount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        {{ $rule['impact'] ?? ($impactAmount >= 0 ? '+' : '') . number_format($impactAmount, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="mt-2 text-sm text-gray-600">No pricing rules matched; legacy defaults were used.</p>
                            @endif
                            @if (!$engineUsed && $legacySource)
                                <p class="mt-2 text-xs text-gray-500">Legacy source: {{ \Illuminate\Support\Str::title($legacySource) }}</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('checkout.paystack') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $pricedBooking->id }}">
                            <div>
                                <x-input-label for="checkout_name" value="Passenger / Contact Name" />
                                <x-text-input id="checkout_name" name="name" type="text" class="mt-1 block w-full"
                                    value="{{ old('name', $pricedBooking->customer_name ?? auth()->user()->name ?? '') }}" />
                            </div>
                            <div>
                                <x-input-label for="checkout_email" value="Contact Email" />
                                <x-text-input id="checkout_email" name="email" type="email" class="mt-1 block w-full"
                                    value="{{ old('email', $pricedBooking->customer_email ?? auth()->user()->email ?? '') }}" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div class="flex items-center gap-3">
                                <x-primary-button>{{ __('Pay with Paystack') }}</x-primary-button>
                                <x-input-error :messages="$errors->get('checkout')" />
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($searchPerformed)
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach ($offers as $offer)
                        @php
                            $tokenPayload = base64_encode(json_encode([
                                'offer_id' => $offer['offer_id'],
                                'owner' => $offer['owner'],
                                'response_id' => $offer['response_id'] ?? null,
                                'currency' => $offer['currency'] ?? config('travelndc.currency', 'USD'),
                                'offer_items' => $offer['offer_items'] ?? [],
                                'segments' => $offer['segments'] ?? [],
                                'primary_carrier' => $offer['primary_carrier'] ?? $offer['owner'],
                                'pricing' => [
                                    'context' => $offer['pricing_context'] ?? ($offer['pricing']['context'] ?? []),
                                    'passengers' => $offer['passenger_summary'] ?? ($offer['pricing']['passengers'] ?? []),
                                ],
                            ], JSON_UNESCAPED_SLASHES) ?: '');
                        @endphp

                        <div class="flex h-full flex-col justify-between rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-indigo-600">
                                        {{ $offer['primary_carrier'] ?? $offer['owner'] }}
                                    </span>
                                    <span class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ $offer['departure_date'] }}
                                        @if (!empty($offer['day_offset']))
                                            ({{ $offer['day_offset'] > 0 ? '+' : '' }}{{ $offer['day_offset'] }} day)
                                        @endif
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm text-gray-700">
                                    @forelse ($offer['segments'] as $segment)
                                        <div class="rounded border border-gray-100 bg-gray-50 p-3">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold">
                                                    {{ $segment['origin'] ?? '---' }}
                                                    →
                                                    {{ $segment['destination'] ?? '---' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ $segment['marketing_carrier'] ?? '' }}
                                                    {{ $segment['marketing_flight_number'] ?? '' }}
                                                </span>
                                            </div>
                                            <div class="mt-2 grid gap-2 text-xs text-gray-500 md:grid-cols-2">
                                                <div>
                                                    Depart:
                                                    <span class="font-medium text-gray-700">
                                                        {{ isset($segment['departure']) ? \Carbon\Carbon::parse($segment['departure'])->format('d M Y H:i') : 'N/A' }}
                                                    </span>
                                                </div>
                                                <div>
                                                    Arrive:
                                                    <span class="font-medium text-gray-700">
                                                        {{ isset($segment['arrival']) ? \Carbon\Carbon::parse($segment['arrival'])->format('d M Y H:i') : 'N/A' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p>No segment information available.</p>
                                    @endforelse
                                </div>
                            </div>

                            @php
                                $pricingData = $offer['pricing'] ?? [];
                                $ndc = $pricingData['ndc'] ?? [];
                                $baseFare = $ndc['base_amount'] ?? ($pricingData['base_amount'] ?? 0);
                                $taxes = $ndc['tax_amount'] ?? ($pricingData['tax_amount'] ?? 0);
                                $adjustments = $pricingData['components']['adjustments'] ?? round(($pricingData['payable_total'] ?? 0) - ($baseFare + $taxes), 2);
                                $engineUsed = data_get($pricingData, 'engine.used', false);
                                $rulesApplied = $pricingData['rules_applied'] ?? [];
                                $ruleCount = is_countable($rulesApplied) ? count($rulesApplied) : 0;
                                $currency = $offer['currency'] ?? config('travelndc.currency', 'USD');
                            @endphp
                            <div class="mt-4 space-y-3 border-t border-gray-100 pt-4 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Base Fare</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $currency }} {{ number_format($baseFare, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Taxes</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $currency }} {{ number_format($taxes, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between {{ $adjustments >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    <span>Adjustments</span>
                                    <span class="font-semibold">
                                        {{ $adjustments >= 0 ? '+' : '' }}{{ number_format($adjustments, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-lg font-bold text-indigo-700">
                                    <span>Total Payable</span>
                                    <span>
                                        {{ $currency }} {{ number_format($pricingData['payable_total'] ?? 0, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span>
                                        @if ($engineUsed && $ruleCount > 0)
                                            {{ $ruleCount }} {{ \Illuminate\Support\Str::plural('rule', $ruleCount) }} applied
                                        @else
                                            Legacy pricing applied
                                        @endif
                                    </span>
                                    <span>
                                        {{ $engineUsed ? 'Engine' : ($pricingData['legacy']['source'] ?? 'default') }}
                                    </span>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('offers.price') }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="offer_token" value="{{ $tokenPayload }}">
                                <button type="submit"
                                    class="mt-2 w-full rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Price This Offer
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded border border-gray-200 bg-white p-8 text-center text-gray-500">
                    Search for flights to see available offers.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
