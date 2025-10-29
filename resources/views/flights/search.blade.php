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
                <div class="rounded border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-semibold">Latest Offer Pricing</p>
                                <p class="text-sm">
                                    Offer {{ $pricedOffer['offer_id'] }} ({{ $pricedOffer['owner'] }}):
                                    {{ $pricedOffer['currency'] }}
                                    {{ number_format($pricedOffer['pricing']['payable_total'], 2) }}
                                </p>
                                <p class="text-xs text-gray-700 md:max-w-sm">
                                    Breakdown: Base {{ $pricedOffer['currency'] }}
                                    {{ number_format($pricedOffer['pricing']['components']['base_fare'] ?? $pricedOffer['pricing']['ndc']['base_amount'] ?? 0, 2) }}
                                    + Taxes {{ $pricedOffer['currency'] }}
                                    {{ number_format($pricedOffer['pricing']['components']['taxes'] ?? $pricedOffer['pricing']['ndc']['tax_amount'] ?? 0, 2) }}
                                    + Commission {{ $pricedOffer['currency'] }}
                                    {{ number_format($pricedOffer['pricing']['components']['commission'] ?? $pricedOffer['pricing']['commission']['commission_amount'] ?? 0, 2) }}
                                    = {{ $pricedOffer['currency'] }}
                                    {{ number_format($pricedOffer['pricing']['payable_total'], 2) }}
                                </p>
                            </div>
                            <div class="rounded bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-900">
                                Commission ({{ $pricedOffer['pricing']['commission']['percent_rate'] ?? $pricedOffer['pricing']['markup']['percent_rate'] ?? 0 }}%):
                                {{ number_format($pricedOffer['pricing']['commission']['commission_amount'] ?? $pricedOffer['pricing']['markup']['markup_amount'] ?? 0, 2) }}
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Booking Ref</p>
                                <p class="text-lg font-semibold text-gray-800">#{{ $pricedBooking->id }}</p>
                                <p class="mt-1 text-sm text-gray-600">Status: {{ ucfirst($pricedBooking->status) }}</p>
                            </div>
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Payment Reference</p>
                                <p class="text-sm text-gray-700">{{ $pricedBooking->payment_reference ?? '—' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Total payable: {{ $pricedOffer['currency'] }} {{ number_format($pricedOffer['pricing']['payable_total'], 2) }}</p>
                            </div>
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

                            <div class="mt-4 space-y-3 border-t border-gray-100 pt-4 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Base Fare</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $offer['currency'] ?? config('travelndc.currency', 'USD') }}
                                        {{ number_format($offer['pricing']['components']['base_fare'] ?? $offer['pricing']['base_amount'] ?? 0, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Taxes</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $offer['currency'] ?? config('travelndc.currency', 'USD') }}
                                        {{ number_format($offer['pricing']['components']['taxes'] ?? $offer['pricing']['tax_amount'] ?? 0, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-emerald-700">
                                    <span>Commission ({{ $offer['pricing']['commission']['percent_rate'] ?? $offer['pricing']['markup']['percent_rate'] ?? 0 }}%)</span>
                                    <span class="font-semibold">
                                        {{ number_format($offer['pricing']['commission']['commission_amount'] ?? $offer['pricing']['markup']['markup_amount'] ?? 0, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-lg font-bold text-indigo-700">
                                    <span>Total Payable</span>
                                    <span>
                                        {{ $offer['currency'] ?? config('travelndc.currency', 'USD') }}
                                        {{ number_format($offer['pricing']['display_total'] ?? $offer['pricing']['payable_total'] ?? 0, 2) }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400">
                                    Commission source: {{ $offer['pricing']['commission']['source'] ?? $offer['pricing']['markup']['source'] ?? 'default' }}
                                    ({{ $offer['pricing']['commission']['percent_rate'] ?? $offer['pricing']['markup']['percent_rate'] ?? 0 }}% of base fare)
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
