@php
    $search = $searchParams ?? [];
    $selectedAirlines = $selectedAirlines ?? [];
    $interlineValue = strtoupper((string) old('interline', $search['interline'] ?? ''));
    if (!in_array($interlineValue, ['Y', 'N', 'D'], true)) {
        $interlineValue = '';
    }
    $airlineLookup = collect($airlineLookup ?? config('airlines', []))
        ->mapWithKeys(fn ($name, $code) => [strtoupper($code) => (string) $name])
        ->all();
    $airlineOptions = collect($airlineOptions ?? [])
        ->map(function ($option, $key) {
            $code = strtoupper((string) data_get($option, 'code', is_string($key) ? $key : ''));
            $name = (string) data_get($option, 'name', '');

            return [
                'code' => $code,
                'name' => $name !== '' ? $name : $code,
            ];
        })
        ->filter(fn ($option) => $option['code'] !== '')
        ->values()
        ->all();

    if (empty($airlineOptions)) {
        $airlineOptions = collect($airlineLookup)
            ->map(fn ($name, $code) => [
                'code' => $code,
                'name' => $name,
            ])
            ->values()
            ->all();
    }

    $currencyFallback = $currencyFallback ?? config('travelndc.currency', 'USD');
    $tripType = old('trip_type', $search['trip_type'] ?? ($search['return_date'] ? 'return' : 'one_way'));
    $tripType = in_array($tripType, ['return', 'one_way', 'multi_city'], true) ? $tripType : 'return';
    if ($tripType === 'multi_city') {
        $tripType = 'return';
    }
    $pricedOffer = $pricedOffer ?? null;
    $pricedBooking = $pricedBooking ?? null;
    $scrollTarget = $scrollTo ?? null;
    if (!$scrollTarget && !empty($pricedOffer) && !empty($pricedBooking)) {
        $scrollTarget = 'itinerary-card';
    }
    $preselectedAirlines = collect(old('selected_airlines', $selectedAirlines))
        ->map(fn ($code) => strtoupper((string) $code))
        ->filter()
        ->values()
        ->all();
    $selectedAirlineSummary = collect($selectedAirlines)
        ->map(function ($code) use ($airlineLookup) {
            $upper = strtoupper((string) $code);
            $name = $airlineLookup[$upper] ?? null;

            return $name ? "{$upper} – {$name}" : $upper;
        })
        ->filter()
        ->values()
        ->all();
    $filterAirlines = collect($airlineOptions ?? config('airlines', []))
        ->map(function ($option, $key) {
            $code = strtoupper((string) data_get($option, 'code', is_string($key) ? $key : ''));
            $label = (string) data_get($option, 'name', $code);

            return [
                'code' => $code,
                'label' => $label !== '' ? $label : $code,
            ];
        })
        ->filter(fn ($airline) => $airline['code'] !== '')
        ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
        ->values()
        ->all();
    $flexibleBuckets = isset($flexibleBuckets) && $flexibleBuckets instanceof \Illuminate\Support\Collection
        ? $flexibleBuckets
        : collect($flexibleBuckets ?? []);
    $activeFlexOffset = isset($activeFlexOffset) ? (int) $activeFlexOffset : 0;
    $offersCollection = isset($offers) && $offers instanceof \Illuminate\Support\Collection ? $offers : collect();
    $hasFlexibleBuckets = $flexibleBuckets->isNotEmpty();
    $activeAirlineFilters = $preselectedAirlines;
    if (!empty($activeAirlineFilters)) {
        $visibleOffers = $offersCollection
            ->filter(function ($offer) use ($activeAirlineFilters) {
                $carrier = strtoupper((string) data_get($offer, 'primary_carrier', data_get($offer, 'owner', '')));

                return $carrier !== '' && in_array($carrier, $activeAirlineFilters, true);
            })
            ->values();

        if ($visibleOffers->isEmpty()) {
            $visibleOffers = collect();
        }
    } else {
        $visibleOffers = $offersCollection;
    }

    $offersCount = $visibleOffers->count();
    $summaryOffers = array_merge([
        'best' => null,
        'cheapest' => null,
        'next_best' => null,
    ], $summaryOffers ?? []);
    $currentSort = old('sort', $search['sort'] ?? ($sortOption ?? 'best'));
    $allowedSorts = ['best', 'cheapest', 'next_best'];
    if (!in_array($currentSort, $allowedSorts, true)) {
        $currentSort = 'best';
    }
    $sortLabels = [
        'best' => 'Best',
        'cheapest' => 'Cheapest',
        'next_best' => 'Next Best',
    ];
    $currentSortLabel = $sortLabels[$currentSort] ?? 'Best';
    $summaryCardConfig = [
        'best' => 'Great balance of price & time',
        'cheapest' => 'Lowest fare available',
        'next_best' => 'Close alternative',
    ];
    $ruleUsageLabels = [
        \App\Models\PricingRule::USAGE_COMMISSION_BASE => 'Commission from base price',
        \App\Models\PricingRule::USAGE_DISCOUNT_BASE => 'Discount from base price',
        \App\Models\PricingRule::USAGE_DISCOUNT_TOTAL_PROMO => 'Discount from total price',
        \App\Models\PricingRule::USAGE_COMMISSION_DISCOUNT_BASE => 'Commission & discount from base price',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Flight Search') }}
        </h2>
    </x-slot>

    <div class="mt-4 border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <form method="GET" action="<?= route('flights.search') ?>" id="flight-search-form" class="space-y-4">
            <input type="hidden" name="trip_type" id="trip_type_input" value="<?= htmlspecialchars($tripType) ?>">
            <input type="hidden" name="sort" id="sortInput" value="<?= htmlspecialchars($currentSort) ?>">
            <div class="flex flex-wrap items-end gap-4 pb-4 border-b border-slate-100">
        
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Trip Type</label>
            <div class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-semibold text-slate-600 shadow-sm">
                <button
                    type="button"
                    data-trip-type="return"
                    class="trip-type-btn rounded-full px-3 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 <?= $tripType === 'return' ? 'bg-black text-white shadow-md border border-black' : 'bg-white text-slate-600 border border-transparent hover:text-slate-900 hover:bg-slate-50' ?>"
                >
                    Return
                </button>
                <button
                    type="button"
                    data-trip-type="one_way"
                    class="trip-type-btn rounded-full px-3 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 <?= $tripType === 'one_way' ? 'bg-black text-white shadow-md border border-black' : 'bg-white text-slate-600 border border-transparent hover:text-slate-900 hover:bg-slate-50' ?>"
                >
                    One-way
                </button>
                <button
                    type="button"
                    data-trip-type="multi_city"
                    disabled
                    class="trip-type-btn rounded-full border border-transparent bg-white px-3 py-2 text-slate-400 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed"
                    title="Multi-city search is coming soon">
                    Multi-city
                </button>
            </div>
        </div>

        <div class="flex-1 min-w-[200px]" data-airport-selector="origin">
            <div class="flex items-center justify-between mb-1">
                <label for="origin_search" class="block text-sm font-medium text-gray-700">From</label>
                <button type="button" id="swap_routes"
                    class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500 shadow-sm transition hover:text-sky-600 lg:hidden">
                    Swap
                </button>
            </div>
            <input type="hidden" id="origin" name="origin" value="<?= htmlspecialchars(old('origin', $search['origin'] ?? '')) ?>">
            <div class="flex flex-wrap gap-2" data-airport-selected></div>
            <div class="relative">
                <input id="origin_search" type="text" data-airport-search
                    class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                    placeholder="Search city, country or code" autocomplete="off">
                <div
                    class="airport-dropdown absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl hidden"
                    data-airport-dropdown></div>
            </div>
            <?php if($errors->has('origin')): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors->first('origin') ?></p>
            <?php endif; ?>
        </div>

        <div class="flex-1 min-w-[200px]" data-airport-selector="destination">
            <label for="destination_search" class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input type="hidden" id="destination" name="destination" value="<?= htmlspecialchars(old('destination', $search['destination'] ?? '')) ?>">
            <div class="flex flex-wrap gap-2" data-airport-selected></div>
            <div class="relative">
                <input id="destination_search" type="text" data-airport-search
                    class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                    placeholder="Search city, country or code" autocomplete="off">
                <div
                    class="airport-dropdown absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl hidden"
                    data-airport-dropdown></div>
            </div>
            <?php if($errors->has('destination')): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors->first('destination') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        
        <div>
            <label for="departure_date" class="block text-sm font-medium text-gray-700 mb-1">Departure</label>
            <input id="departure_date" name="departure_date" type="date" class="block w-40 rounded-lg border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                value="<?= htmlspecialchars(old('departure_date', $search['departure_date'] ?? '')) ?>" />
            <?php if($errors->has('departure_date')): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors->first('departure_date') ?></p>
            <?php endif; ?>
        </div>

        <div id="return_date_wrapper">
            <label for="return_date" class="block text-sm font-medium text-gray-700 mb-1">Return</label>
            <input id="return_date" name="return_date" type="date" class="block w-40 rounded-lg border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                value="<?= htmlspecialchars(old('return_date', $search['return_date'] ?? '')) ?>" />
            <?php if($errors->has('return_date')): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors->first('return_date') ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="cabin_class" class="block text-sm font-medium text-gray-700 mb-1">Cabin</label>
            <select id="cabin_class" name="cabin_class"
                class="block w-32 rounded-lg border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm">
                <?php foreach (['ECONOMY' => 'Economy', 'PREMIUM_ECONOMY' => 'Premium Economy', 'BUSINESS' => 'Business', 'FIRST' => 'First'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($search['cabin_class'] ?? 'ECONOMY') === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if($errors->has('cabin_class')): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors->first('cabin_class') ?></p>
            <?php endif; ?>
        </div>

        <div>
            <span class="block text-xs font-semibold text-slate-500 mb-1">Travellers</span>
            <div class="flex gap-2">
            <div>
                <label for="adults" class="block text-[11px] font-semibold text-slate-500 mb-1 text-center">Adults</label>
                <input id="adults" name="adults" type="number" min="1" max="9"
                    class="block w-16 rounded-lg border-slate-200 text-center shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                    value="<?= htmlspecialchars(old('adults', $search['adults'] ?? 1)) ?>" />
            </div>
            <div>
                <label for="children" class="block text-[11px] font-semibold text-slate-500 mb-1 text-center">Children</label>
                <input id="children" name="children" type="number" min="0" max="9"
                    class="block w-16 rounded-lg border-slate-200 text-center shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                    value="<?= htmlspecialchars(old('children', $search['children'] ?? 0)) ?>" />
            </div>
            <div>
                <label for="infants" class="block text-[11px] font-semibold text-slate-500 mb-1 text-center">Infants</label>
                <input id="infants" name="infants" type="number" min="0" max="9"
                    class="block w-16 rounded-lg border-slate-200 text-center shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                    value="<?= htmlspecialchars(old('infants', $search['infants'] ?? 0)) ?>" />
            </div>
            </div>
        </div>

        <div class="flex items-center gap-3 ml-auto">
            <button type="reset" class="inline-flex items-center justify-center px-5 py-2 rounded-full border border-slate-200 bg-white text-sm font-semibold uppercase tracking-wide text-slate-900 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                <?= __('Reset') ?>
            </button>
            <button type="submit" class="inline-flex items-center justify-center px-5 py-2 rounded-full border border-transparent bg-gradient-to-br from-indigo-600 to-purple-600 text-sm font-semibold uppercase tracking-wide text-slate-900 shadow-lg transition hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                <?= __('Search Flights') ?>
            </button>
        </div>
    </div>
</form>
        </div>
    </div>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            @if (session('ref'))
                <div class="rounded border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800">
                    Referral code active: <span class="font-semibold">{{ session('ref') }}</span>
                </div>
            @endif

            @if ($errorMessage)
                <div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errorMessage }}
                </div>
            @endif

            @if ($errors->has('hold'))
                <div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first('hold') }}
                </div>
            @endif

            @if (!empty($videcomHold))
                @php
                    $pnrCode = data_get($videcomHold, 'RLOC') ?? data_get($videcomHold, 'pnr');
                    $ttlDate = data_get($videcomHold, 'TimeLimits.TTL.TTLDate');
                    $ttlTime = data_get($videcomHold, 'TimeLimits.TTL.TTLTime');
                    $ttlCity = data_get($videcomHold, 'TimeLimits.TTL.TTLCity');
                @endphp
                <div id="videcom-hold" class="rounded border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                    <p class="font-semibold">Videcom booking hold confirmed.</p>
                    <p class="text-sm">
                        PNR: <span class="font-mono font-semibold">{{ $pnrCode ?? 'N/A' }}</span>
                        @if ($ttlDate || $ttlTime)
                            – Held until {{ $ttlDate ?? '' }} {{ $ttlTime ?? '' }} {{ $ttlCity ?? '' }}
                        @endif
                    </p>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-semibold text-emerald-800">View raw response</summary>
                        <pre class="mt-2 overflow-auto rounded bg-white p-3 text-xs text-emerald-900">{{ json_encode($videcomHold, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
            @endif

            @if ($orderDetails = session('travelNdcOrder'))
                <div class="rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="font-semibold">TravelNDC order created.</p>
                    <p>Order ID: <span class="font-mono">{{ $orderDetails['order_id'] ?? '—' }}</span></p>
                    @if (!empty($orderDetails['response_id']))
                        <p>Response ID: <span class="font-mono">{{ $orderDetails['response_id'] }}</span></p>
                    @endif
                </div>
            @endif

            @if ($errors->has('ndc_order'))
                <div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first('ndc_order') }}
                </div>
            @endif
            @if ($errors->has('ndc_payment'))
                <div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first('ndc_payment') }}
                </div>
            @endif

            @if ($tickets = session('travelNdcTickets'))
                <div class="rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="font-semibold">TravelNDC tickets issued.</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($tickets as $ticket)
                            <li class="font-mono">{{ $ticket }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- UPDATED GRID LAYOUT: 30% Left, 40% Right -->
            <div class="grid gap-6 lg:grid-cols-[30%_40%]">
            <aside class="lg:sticky lg:top-6" style="width: 116%;">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                            <label for="interline" class="block text-sm font-semibold text-slate-800">Interline</label>
                            <select
                                name="interline"
                                id="interline"
                                form="flight-search-form"
                                class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            >
                                <option value="" @selected($interlineValue === '')>Without restrictions</option>
                                <option value="Y" @selected($interlineValue === 'Y')>Different marketing carriers</option>
                                <option value="N" @selected($interlineValue === 'N')>Plating carrier only</option>
                                <option value="D" @selected($interlineValue === 'D')>Only other than plating carrier</option>
                            </select>
                        </div>

                        <div class="space-y-4 rounded-xl border border-slate-100 bg-slate-50/60 p-3" data-airline-filter-wrapper>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <span>Airlines</span>
                                    <span class="text-xs text-slate-500" data-airline-selection-count>{{ empty($preselectedAirlines) ? 'All' : count($preselectedAirlines) . ' selected' }}</span>
                                </div>
                                <button type="button" data-clear-airline-filters class="text-xs font-semibold text-sky-700 hover:text-sky-800">
                                    Clear
                                </button>
                            </div>
                            <div class="mt-2">
                                <input
                                    type="text"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                    placeholder="Search airlines..."
                                    data-airline-search
                                >
                            </div>
                            <div class="space-y-2 max-h-60 overflow-y-auto pr-1" data-airline-list>
                                @forelse ($filterAirlines as $airline)
                                    <label class="flex items-center gap-3 rounded-lg border border-slate-100 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-slate-50">
                                        <input
                                            type="checkbox"
                                            name="selected_airlines[]"
                                            value="{{ $airline['code'] }}"
                                            form="flight-search-form"
                                            class="h-4 w-4 rounded text-sky-600 focus:ring-sky-500"
                                            data-airline-filter
                                            @checked(in_array($airline['code'], $preselectedAirlines, true))
                                        >
                                        <span class="flex-1">
                                            <span class="font-semibold text-slate-900">{{ $airline['code'] }}</span>
                                            @if ($airline['label'] !== $airline['code'])
                                                <span class="ml-1 text-slate-500">– {{ $airline['label'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500">No airlines available for this search.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

                <div class="space-y-4" data-results-anchor style="width: 178%;padding-left: 60px;">
                    @if ($searchPerformed && isset($dateRangeSummaries) && $dateRangeSummaries->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-800">Flexible dates (±3 days)</p>
                                <p class="text-xs text-slate-500">Select a range to refresh results</p>
                            </div>
                            <div class="overflow-x-auto">
                                <div class="flex min-w-max gap-3" data-flex-options data-default-offset="{{ $activeFlexOffset }}">
                                    @foreach ($dateRangeSummaries as $summary)
                                        @php
                                            $rangeEnd = $summary['end'] ?? $summary['start'];
                                            $offset = $summary['offset'];
                                            $isActiveFlex = (int) $offset === (int) $activeFlexOffset;
                                            $activeClasses = 'border-indigo-500 bg-indigo-50 text-indigo-900 shadow';
                                            $inactiveClasses = 'border-slate-200 bg-white text-slate-700 hover:border-indigo-300';
                                        @endphp
                                        <button
                                            type="button"
                                            data-flex-option
                                            data-flex-offset="{{ $offset }}"
                                            data-active-class="{{ $activeClasses }}"
                                            data-inactive-class="{{ $inactiveClasses }}"
                                            class="flex w-48 flex-col rounded-xl border px-4 py-3 text-left transition {{ $isActiveFlex ? $activeClasses : $inactiveClasses }}"
                                            aria-pressed="{{ $isActiveFlex ? 'true' : 'false' }}"
                                        >
                                            <span class="text-xs font-semibold uppercase text-slate-500">
                                                {{ $summary['start']->format('d M') }}
                                                –
                                                {{ $rangeEnd->format('d M') }}
                                            </span>
                                            <span class="mt-2 text-lg font-bold">
                                                @if ($summary['price'])
                                                    {{ $summary['currency'] }} {{ number_format($summary['price'], 2) }}
                                                @else
                                                    <span class="text-sm font-semibold text-slate-500">No fares</span>
                                                @endif
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                {{ $summary['count'] }}
                                                {{ \Illuminate\Support\Str::plural('offer', $summary['count']) }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($searchPerformed)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" data-offer-count data-sort-label="{{ $currentSortLabel }}">
                                    {{ $offersCount }} {{ \Illuminate\Support\Str::plural('result', $offersCount) }} sorted by {{ $currentSortLabel }}
                                </span>
                                <button class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" type="button">
                                    Get price alerts
                                </button>
                            </div>
                            <span class="text-xs text-slate-500" data-airline-filter-summary data-default-text="Showing all airlines">
                                @if (!empty($selectedAirlineSummary))
                                    Filtered: {{ implode(', ', $selectedAirlineSummary) }}
                                @else
                                    Showing all airlines
                                @endif
                            </span>
                        </div>

                        @php
                            $hasSummaryData = collect($summaryOffers)->filter()->isNotEmpty();
                        @endphp
                        @if ($offersCount > 0 && $hasSummaryData)
                            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800 shadow-sm md:grid-cols-3" data-offer-stats>
                                @foreach (['best', 'cheapest', 'next_best'] as $key)
                                    @php
                                        $offerSummary = $summaryOffers[$key] ?? null;
                                        if (!$offerSummary) {
                                            continue;
                                        }

                                        $pricingData = $offerSummary['pricing'] ?? [];
                                        $amount = (float) ($pricingData['payable_total'] ?? $pricingData['total_amount'] ?? 0);
                                        $currency = $offerSummary['currency'] ?? $currencyFallback;
                                        $isActive = $currentSort === $key;
                                        $btnClasses = $isActive
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-900 shadow'
                                            : 'border-slate-200 bg-white/80 text-slate-800 hover:border-indigo-300';
                                        $label = $sortLabels[$key] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $key));
                                    @endphp
                                    <button
                                        type="button"
                                        class="rounded-xl border px-3 py-3 text-left shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $btnClasses }}"
                                        data-stat-card
                                        data-stat-type="{{ $key }}"
                                        data-highlight-offer="{{ $offerSummary['offer_id'] ?? '' }}"
                                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                    >
                                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p>
                                        <p class="text-lg font-bold text-slate-900" data-stat-value data-currency="{{ $currency }}" data-amount="{{ $amount }}">
                                            {{ $currency }} {{ number_format($amount, 2) }}
                                        </p>
                                        <p class="text-[11px] text-slate-500">{{ $summaryCardConfig[$key] ?? '' }}</p>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if (!$hasFlexibleBuckets)
                            <div class="rounded border border-yellow-200 bg-yellow-50 p-8 text-center text-yellow-900">
                                No flight offers were found for the selected criteria. Try adjusting the dates, airports, or airline filters.
                            </div>
                        @else
                            <div data-flex-buckets>
                                @foreach ($flexibleBuckets as $offset => $bucket)
                                    @php
                                        $bucketOffers = ($bucket['offers'] ?? collect()) instanceof \Illuminate\Support\Collection
                                            ? $bucket['offers']
                                            : collect($bucket['offers'] ?? []);
                                        $isActiveBucket = (int) $offset === (int) $activeFlexOffset;
                                    @endphp
                                    <div class="{{ $isActiveBucket ? '' : 'hidden' }}" data-offer-bucket data-flex-offset="{{ $offset }}">
                                        @if ($bucketOffers->isEmpty())
                                            <div class="rounded border border-yellow-200 bg-yellow-50 p-8 text-center text-yellow-900">
                                                No flight offers were found for the selected criteria. Try adjusting the dates, airports, or airline filters.
                                            </div>
                                        @else
                                            <div class="space-y-4" data-offer-list>
                                                @foreach ($bucketOffers as $offer)
                                                @php
                                                    $tokenPayload = base64_encode(json_encode([
                                                        'offer_id' => $offer['offer_id'],
                                                        'owner' => $offer['owner'],
                                                        'response_id' => $offer['response_id'] ?? null,
                                                        'currency' => $offer['currency'] ?? $currencyFallback,
                                                        'offer_items' => $offer['offer_items'] ?? [],
                                                        'segments' => $offer['segments'] ?? [],
                                                        'primary_carrier' => $offer['primary_carrier'] ?? $offer['owner'],
                                                        'demo_provider' => $offer['demo_provider'] ?? null,
                                                        'ndc_pricing' => \Illuminate\Support\Arr::only(
                                                            $offer['ndc_pricing'] ?? ($offer['pricing'] ?? []),
                                                            ['base_amount', 'tax_amount', 'total_amount']
                                                        ),
                                                        'pricing' => [
                                                            'context' => $offer['pricing_context'] ?? ($offer['pricing']['context'] ?? []),
                                                            'passengers' => $offer['passenger_summary'] ?? ($offer['pricing']['passengers'] ?? []),
                                                        ],
                                                    ], JSON_UNESCAPED_SLASHES) ?: '');

                                                    $pricingData = $offer['pricing'] ?? [];
                                                    $ndc = $pricingData['ndc'] ?? [];
                                                    $baseFare = $ndc['base_amount'] ?? ($pricingData['base_amount'] ?? 0);
                                                    $taxes = $ndc['tax_amount'] ?? ($pricingData['tax_amount'] ?? 0);
                                                    $adjustments = $pricingData['components']['adjustments'] ?? round(($pricingData['payable_total'] ?? 0) - ($baseFare + $taxes), 2);
                                                    $engineUsed = data_get($pricingData, 'engine.used', false);
                                                    $rulesApplied = $pricingData['rules_applied'] ?? [];
                                                    $ruleCount = is_countable($rulesApplied) ? count($rulesApplied) : 0;
                                                    $commissionRules = collect($rulesApplied)
                                                        ->filter(function ($rule) {
                                                            $kind = strtoupper((string) ($rule['kind'] ?? ''));
                                                            $usage = strtolower((string) ($rule['usage'] ?? ''));
                                                            $label = strtolower((string) ($rule['label'] ?? ''));

                                                            return $kind === \App\Models\PricingRule::KIND_COMMISSION
                                                                || \Illuminate\Support\Str::startsWith($usage, 'commission')
                                                                || \Illuminate\Support\Str::contains($label, 'commission');
                                                        })
                                                        ->values();
                                                    $commissionRule = $commissionRules->first();
                                                    $commissionImpact = $commissionRule ? (float) ($commissionRule['impact_amount'] ?? 0) : 0.0;
                                                    $commissionImpactLabel = $commissionRule
                                                        ? (($commissionImpact >= 0 ? '+' : '-') . number_format(abs($commissionImpact), 2))
                                                        : null;
                                                    $commissionLabel = null;
                                                    if ($commissionRule) {
                                                        $usageKey = $commissionRule['usage'] ?? null;
                                                        $usageLabel = $usageKey && isset($ruleUsageLabels[$usageKey]) ? $ruleUsageLabels[$usageKey] : null;
                                                        $commissionLabel = $commissionRule['label']
                                                            ?? ($commissionRule['id'] ? "Rule #{$commissionRule['id']}" : 'Commission');

                                                        if ($usageLabel) {
                                                            $commissionLabel .= " – {$usageLabel}";
                                                        }
                                                    } elseif ($engineUsed && $ruleCount > 0) {
                                                        $commissionLabel = 'Commission rule not triggered';
                                                    }
                                                    $currency = $offer['currency'] ?? $currencyFallback;
                                                    $primaryCarrier = strtoupper($offer['primary_carrier'] ?? $offer['owner'] ?? '');
                                                    $displayCarrier = $offer['airline_name'] ?? ($offer['primary_carrier'] ?? $offer['owner']);
                                                    $totalPayable = (float) ($pricingData['payable_total'] ?? $pricingData['total_amount'] ?? 0);
                                                    $shouldHideInitial = !empty($preselectedAirlines) && !in_array($primaryCarrier, $preselectedAirlines, true);
                                                @endphp

                                            <div class="{{ $shouldHideInitial ? 'hidden ' : '' }}flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" data-offer-card data-offer-id="{{ $offer['offer_id'] }}" data-flex-offset="{{ $offset }}" data-airline-code="{{ $primaryCarrier }}" data-offer-price="{{ $totalPayable }}" data-offer-currency="{{ $currency }}" data-interline-type="{{ $offer['interline_type'] ?? '' }}">
                                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                                            <div class="flex items-center gap-3">
                                                                <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-sm font-semibold text-slate-700">
                                                                    {{ \Illuminate\Support\Str::limit($displayCarrier, 2, '') }}
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm font-semibold text-indigo-700">
                                                                        {{ $displayCarrier }}
                                                                    </p>
                                                                    <p class="text-xs text-slate-500">
                                                                        {{ $offer['departure_date'] }}
                                                                        @if (!empty($offer['day_offset']))
                                                                            ({{ $offer['day_offset'] > 0 ? '+' : '' }}{{ $offer['day_offset'] }} day)
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-3 text-right">
                                                                <div class="text-lg font-bold text-slate-900">
                                                                    {{ $currency }} {{ number_format($totalPayable, 2) }}
                                                                </div>
                                                                <div class="text-[11px] text-slate-500">
                                                                    <div>Base: {{ $currency }} {{ number_format($baseFare, 2) }}</div>
                                                                    <div>Taxes: {{ $currency }} {{ number_format($taxes, 2) }}</div>
                                                                </div>
                                                                <form method="POST" action="{{ route('offers.price') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="offer_token" value="{{ $tokenPayload }}">
                                                                    <button type="submit"
                                                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                                        Select →
                                                                    </button>
                                                                </form>
                                                            </div>
                                                    </div>

                                                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-600">
                                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-700">
                                                            {{ $ruleCount }} {{ \Illuminate\Support\Str::plural('rule', $ruleCount) }}
                                                        </span>
                                                        @if ($commissionRule)
                                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 font-semibold text-indigo-700">
                                                                Commission: {{ $commissionLabel }}
                                                            </span>
                                                            @if ($commissionImpactLabel !== null)
                                                                <span class="inline-flex items-center rounded-full px-2 py-1 font-semibold {{ $commissionImpact >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                                    Impact {{ $commissionImpactLabel }}
                                                                </span>
                                                            @endif
                                                        @elseif ($engineUsed)
                                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 font-semibold text-amber-700">
                                                                No commission rule matched
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-700">
                                                                Legacy pricing
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="space-y-2 text-sm text-gray-700">
                                                        @forelse ($offer['segments'] as $segment)
                                                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                                    <span class="text-sm font-semibold text-slate-800 segment-route"
                                                                          data-origin="{{ $segment['origin'] ?? '' }}"
                                                                          data-destination="{{ $segment['destination'] ?? '' }}">
                                                                        {{ $segment['origin'] ?? '---' }} → {{ $segment['destination'] ?? '---' }}
                                                                    </span>
                                                                    <span class="text-xs text-gray-500">
                                                                        {{ $segment['marketing_carrier'] ?? '' }}
                                                                        {{ $segment['marketing_flight_number'] ?? '' }}
                                                                    </span>
                                                                </div>
                                                                <div class="mt-2 grid gap-3 text-xs text-gray-500 sm:grid-cols-2">
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
                                                            <p class="text-sm text-slate-500">No segment information available.</p>
                                                        @endforelse
                                                    </div>

                                            @if (($offer['demo_provider'] ?? null) === 'videcom')
                                                <div class="mt-5 border-t border-gray-100 pt-4">
                                                    <p class="text-sm font-semibold text-gray-800">Hold this itinerary with Videcom</p>
                                                    <p class="text-xs text-gray-500">Creates a temporary reservation directly with Videcom.</p>

                                                    <form method="POST" action="{{ route('offers.hold') }}" class="mt-3 space-y-3">
                                                            @csrf
                                                            <input type="hidden" name="offer_token" value="{{ $tokenPayload }}">

                                                            <div class="grid gap-3 md:grid-cols-3">
                                                                <div>
                                                                    <x-input-label for="passenger_title_{{ $loop->parent->index }}_{{ $loop->index }}" value="Title" />
                                                                    <select id="passenger_title_{{ $loop->parent->index }}_{{ $loop->index }}" name="passenger_title"
                                                                        class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                                        @foreach (['MR', 'MRS', 'MS'] as $title)
                                                                            <option value="{{ $title }}" @selected(old('passenger_title', 'MR') === $title)>
                                                                                {{ $title }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <x-input-error :messages="$errors->get('passenger_title')" class="mt-1" />
                                                                </div>
                                                                <div>
                                                                    <x-input-label for="passenger_first_name_{{ $loop->parent->index }}_{{ $loop->index }}" value="First Name" />
                                                                    <x-text-input id="passenger_first_name_{{ $loop->parent->index }}_{{ $loop->index }}" name="passenger_first_name" type="text"
                                                                        class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                                        value="{{ old('passenger_first_name', auth()->user()?->name ? explode(' ', auth()->user()->name, 2)[0] : '') }}" />
                                                                    <x-input-error :messages="$errors->get('passenger_first_name')" class="mt-1" />
                                                                </div>
                                                                <div>
                                                                    <x-input-label for="passenger_last_name_{{ $loop->parent->index }}_{{ $loop->index }}" value="Last Name" />
                                                                    <x-text-input id="passenger_last_name_{{ $loop->parent->index }}_{{ $loop->index }}" name="passenger_last_name" type="text"
                                                                        class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                                        value="{{ old('passenger_last_name') }}" />
                                                                    <x-input-error :messages="$errors->get('passenger_last_name')" class="mt-1" />
                                                                </div>
                                                            </div>

                                                            <div class="grid gap-3 md:grid-cols-2">
                                                                <div>
                                                                    <x-input-label for="contact_email_{{ $loop->parent->index }}_{{ $loop->index }}" value="Contact Email" />
                                                                    <x-text-input id="contact_email_{{ $loop->parent->index }}_{{ $loop->index }}" name="contact_email" type="email"
                                                                        class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                                        value="{{ old('contact_email', auth()->user()?->email) }}" />
                                                                    <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                                                                </div>
                                                                <div>
                                                                    <x-input-label for="contact_phone_{{ $loop->parent->index }}_{{ $loop->index }}" value="Contact Phone" />
                                                                    <x-text-input id="contact_phone_{{ $loop->parent->index }}_{{ $loop->index }}" name="contact_phone" type="text"
                                                                        class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                                        value="{{ old('contact_phone') }}" />
                                                                    <x-input-error :messages="$errors->get('contact_phone')" class="mt-1" />
                                                                </div>
                                                            </div>

                                                            <x-primary-button class="w-full justify-center">
                                                                {{ __('Hold Booking via Videcom') }}
                                                            </x-primary-button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="rounded border border-yellow-200 bg-yellow-50 p-8 text-center text-yellow-900 hidden" data-filter-no-results>
                                No flight offers match the selected filters.
                            </div>
                        @endif
                    </div>
            @else
                <div class="rounded border border-gray-200 bg-white p-8 text-center text-gray-500">
                    Search for flights to see available offers.
                </div>
            @endif
                </div>
            </div>

            @if (!empty($pricedOffer) && !empty($pricedBooking))
                @php
                    $pricing = $pricedOffer['pricing'] ?? [];
                    $currency = $pricedOffer['currency'] ?? $currencyFallback;
                    $ndc = $pricing['ndc'] ?? [];
                    $baseFare = $ndc['base_amount'] ?? ($pricing['base_amount'] ?? 0);
                    $taxes = $ndc['tax_amount'] ?? ($pricing['tax_amount'] ?? 0);
                    $components = $pricing['components'] ?? [];
                    $adjustments = $components['adjustments'] ?? round(($pricing['payable_total'] ?? 0) - ($baseFare + $taxes), 2);
                    $rulesApplied = $pricing['rules_applied'] ?? [];
                    $ruleCount = is_countable($rulesApplied) ? count($rulesApplied) : 0;
                    $engineUsed = data_get($pricing, 'engine.used', false);
                    $legacySource = data_get($pricing, 'legacy.source');
                    $rawOrder = $pricedBooking->provider_order_data ?? [];
                    $orderData = is_array($rawOrder) ? $rawOrder : [];
                    $ndcOrderExists = !empty($pricedBooking->provider_order_id);
                    $ndcTickets = data_get($orderData, 'tickets', []);
                    $requiresNdcOrder = ($pricedOffer['demo_provider'] ?? null) !== 'videcom';
                    $disablePayments = $requiresNdcOrder && !$ndcOrderExists;
                @endphp

                <div id="itinerary-card" class="sr-only h-0 w-0 overflow-hidden">Itinerary anchor</div>
                <div id="payment-options" class="rounded border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
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
                        @if (!empty($pricedOffer['segments']))
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Itinerary</p>
                                <div class="mt-3 space-y-3 text-sm text-gray-700">
                                    @foreach ($pricedOffer['segments'] as $segment)
                                        @php
                                            $departure = !empty($segment['departure']) ? \Illuminate\Support\Carbon::parse($segment['departure'])->format('d M Y, H:i') : null;
                                            $arrival = !empty($segment['arrival']) ? \Illuminate\Support\Carbon::parse($segment['arrival'])->format('d M Y, H:i') : null;
                                        @endphp
                                        <div class="rounded border border-gray-100 bg-gray-50 p-3">
                                            <div class="flex items-center justify-between">
                                                <span class="font-semibold">
                                                    {{ $segment['origin'] ?? '---' }} → {{ $segment['destination'] ?? '---' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ $segment['marketing_carrier'] ?? '' }}
                                                    {{ $segment['marketing_flight_number'] ?? '' }}
                                                </span>
                                            </div>
                                            @if ($departure)
                                                <p class="mt-1 text-xs text-gray-600">Departs: {{ $departure }}</p>
                                            @endif
                                            @if ($arrival)
                                                <p class="text-xs text-gray-600">Arrives: {{ $arrival }}</p>
                                            @endif
                                            @if (!empty($segment['equipment']))
                                                <p class="text-xs text-gray-500">Equipment: {{ $segment['equipment'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($requiresNdcOrder && !empty($pricedOffer['token']))
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">TravelNDC Booking</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    Provide passenger details to create the TravelNDC order before collecting payment.
                                </p>
                                <p class="mt-2 text-sm font-semibold text-emerald-700 {{ $ndcOrderExists ? '' : 'hidden' }}" data-ndc-order-status>
                                    TravelNDC order created. Order ID:
                                    <span class="font-mono" data-ndc-order-status-id>{{ $pricedBooking->provider_order_id }}</span>
                                </p>

                                @if (!$ndcOrderExists)
                                    <form method="POST" action="{{ route('travelndc.orders.store') }}"
                                        class="mt-3 space-y-3" data-ndc-order-form>
                                        @csrf
                                        <input type="hidden" name="offer_token" value="{{ $pricedOffer['token'] }}">
                                        <input type="hidden" name="booking_id" value="{{ $pricedBooking->id }}">

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="ndc_title" value="Title" />
                                                <select id="ndc_title" name="title"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                    @foreach (['MR', 'MRS', 'MS'] as $title)
                                                        <option value="{{ $title }}" @selected(old('title', 'MR') === $title)>
                                                            {{ $title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <x-input-label for="ndc_ptc" value="Passenger Type" />
                                                <select id="ndc_ptc" name="ptc"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                    @foreach (['ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant'] as $code => $label)
                                                        <option value="{{ $code }}" @selected(old('ptc', 'ADT') === $code)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="ndc_gender" value="Gender" />
                                                <select id="ndc_gender" name="gender"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                                    @foreach (['MALE' => 'Male', 'FEMALE' => 'Female'] as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('gender', 'MALE') === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <x-input-label for="ndc_birthdate" value="Birthdate" />
                                                <x-text-input id="ndc_birthdate" name="birthdate" type="date"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    value="{{ old('birthdate') }}" />
                                            </div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="ndc_given_name" value="First Name" />
                                                <x-text-input id="ndc_given_name" name="given_name" type="text"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    value="{{ old('given_name', auth()->user()?->name ? explode(' ', auth()->user()->name, 2)[0] : '') }}" />
                                            </div>
                                            <div>
                                                <x-input-label for="ndc_surname" value="Last Name" />
                                                <x-text-input id="ndc_surname" name="surname" type="text"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    value="{{ old('surname') }}" />
                                            </div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="ndc_contact_email" value="Contact Email" />
                                                <x-text-input id="ndc_contact_email" name="contact_email" type="email"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    value="{{ old('contact_email', auth()->user()?->email) }}" />
                                            </div>
                                            <div>
                                                <x-input-label for="ndc_contact_phone" value="Contact Phone" />
                                                <x-text-input id="ndc_contact_phone" name="contact_phone" type="text"
                                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                                    value="{{ old('contact_phone') }}" placeholder="+123456789" />
                                            </div>
                                        </div>

                                        <x-primary-button class="w-full justify-center" data-ndc-order-submit data-loading-text="{{ __('Creating order...') }}">
                                            {{ __('Create TravelNDC Order') }}
                                        </x-primary-button>
                                        <p class="text-sm text-red-600" data-ndc-order-error></p>
                                    </form>
                                @endif
                            </div>
                        @endif

                        <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm {{ $requiresNdcOrder && !$ndcOrderExists ? 'hidden' : '' }}" data-collect-payment-section>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Collect Payment</p>
                            <p class="mb-3 text-sm text-gray-600" data-payment-instruction>
                                Charge the passenger using Paystack/Stripe. Once the payment succeeds, we will automatically confirm the TravelNDC order and issue tickets.
                            </p>
                            <?php if ($disablePayments): ?>
                                <p class="mb-3 text-sm text-gray-600" data-payment-locked-message>
                                    Complete the TravelNDC booking form above to enable Paystack/Stripe payment.
                                </p>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('checkout.paystack')); ?>" class="space-y-3">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="booking_id" value="<?php echo e($pricedBooking->id); ?>">
                                <div>
                                    <label for="checkout_name" class="text-sm font-medium text-gray-700">Passenger / Contact Name</label>
                                    <input
                                        id="checkout_name"
                                        name="name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                        value="<?php echo e(old('name', $pricedBooking->customer_name ?? (auth()->user()->name ?? ''))); ?>"
                                    >
                                </div>
                                <div>
                                    <label for="checkout_email" class="text-sm font-medium text-gray-700">Contact Email</label>
                                    <input
                                        id="checkout_email"
                                        name="email"
                                        type="email"
                                        class="mt-1 block w-full rounded-md border-slate-200 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                        value="<?php echo e(old('email', $pricedBooking->customer_email ?? (auth()->user()->email ?? ''))); ?>"
                                        required
                                    >
                                    <?php if($errors->has('email')): ?>
                                        <div class="mt-2 text-sm text-red-600"><?php echo e($errors->first('email')); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                        data-payment-button
                                        <?php echo $disablePayments ? 'disabled' : ''; ?>
                                    >
                                        <?php echo e(__('Pay with Paystack')); ?>
                                    </button>
                                    <button
                                        type="button"
                                        data-stripe-url="<?php echo e(route('payments.stripe.checkout', $pricedBooking)); ?>"
                                        class="rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                        data-stripe-button
                                        data-loading-text="<?php echo e(__('Redirecting...')); ?>"
                                        data-payment-button
                                        <?php echo $disablePayments ? 'disabled' : ''; ?>
                                    >
                                        <?php echo e(__('Pay with Stripe')); ?>
                                    </button>
                                    <?php if($errors->has('checkout')): ?>
                                        <div class="text-sm text-red-600"><?php echo e($errors->first('checkout')); ?></div>
                                    <?php endif; ?>
                                    <p data-stripe-error class="text-sm text-red-600"></p>
                                </div>
                            </form>
                        </div>

                        @if (!empty($ndcTickets))
                            <div class="rounded border border-emerald-100 bg-white p-4 shadow-sm">
                                <p class="text-xs uppercase tracking-wide text-gray-500">TravelNDC Tickets</p>
                                <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                                    @foreach ($ndcTickets as $ticket)
                                        <li class="font-mono">{{ $ticket }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

        <style>
            .airline-checkbox-dropdown {
                position: relative;
                font-size: 0.875rem;
                color: #0f172a;
            }

            .airline-checkbox-dropdown .dropdown-label {
                width: 100%;
                text-align: left;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.85rem;
                padding: 0.65rem 0.85rem;
                background-color: #fff;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                font-weight: 600;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.5rem;
                cursor: pointer;
                box-shadow: inset 0 1px 2px rgb(15 23 42 / 0.05);
            }

            .airline-checkbox-dropdown .dropdown-label::after {
                content: "▼";
                font-size: 0.75rem;
                color: #475569;
            }

            .airline-checkbox-dropdown.on .dropdown-label::after {
                content: "▲";
            }

            .airline-checkbox-dropdown .dropdown-list {
                position: absolute;
                top: calc(100% + 0.5rem);
                left: 0;
                right: 0;
                background-color: #fff;
                border: 1px solid rgb(226 232 240);
                border-radius: 1rem;
                box-shadow: 0 20px 40px rgb(15 23 42 / 0.12);
                padding: 0.75rem;
                display: none;
                max-height: 60vh;
                overflow-y: auto;
                z-index: 40;
            }

            .airline-checkbox-dropdown.on .dropdown-list {
                display: block;
            }

            .airline-checkbox-dropdown .dropdown-search {
                position: sticky;
                top: 0;
                background-color: #fff;
                margin-bottom: 0.5rem;
                display: none;
            }

            .airline-checkbox-dropdown .search-input {
                width: 100%;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.65rem;
                padding: 0.4rem 0.75rem;
                font-size: 0.85rem;
                background-color: #fff;
            }

            .airline-checkbox-dropdown.on .dropdown-search {
                display: block;
            }

            .airline-checkbox-dropdown .dropdown-option {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.4rem 0.25rem;
                border-radius: 0.5rem;
                transition: background-color 0.15s ease-in-out;
            }

            .airline-checkbox-dropdown .dropdown-option:hover {
                background-color: #f1f5f9;
            }

            .airline-checkbox-dropdown .dropdown-option input[type="checkbox"] {
                accent-color: #0ea5e9;
            }

            .airline-checkbox-dropdown .dropdown-option.dropdown-action {
                font-weight: 600;
                padding-bottom: 0.75rem;
            }
        </style>

        <script>
            class CheckboxDropdown {
                constructor(element) {
                    this.element = element;
                    this.label = element.querySelector('.dropdown-label');
                    this.toggleAllButton = element.querySelector('[data-toggle="check-all"]');
                    this.searchInput = element.querySelector('.search-input');
                    this.optionLabels = Array.from(element.querySelectorAll('label.dropdown-option'));
                    this.checkboxes = this.optionLabels.map((label) => label.querySelector('input[type="checkbox"]')).filter(Boolean);
                    this.placeholder = element.dataset.placeholder || 'All airlines';
                    this.isOpen = false;
                    this.areAllChecked = false;
                    this.handleDocumentClick = this.handleDocumentClick.bind(this);
                    this.init();
                }

                init() {
                    this.updateStatus();

                    if (this.label) {
                        this.label.addEventListener('click', (event) => {
                            event.preventDefault();
                            this.toggleOpen();
                        });
                    }

                    if (this.toggleAllButton) {
                        this.toggleAllButton.addEventListener('click', (event) => {
                            event.preventDefault();
                            this.toggleAll();
                        });
                    }

                    this.checkboxes.forEach((checkbox) => {
                        checkbox.addEventListener('change', () => this.updateStatus());
                    });

                    if (this.searchInput) {
                        this.searchInput.addEventListener('input', () => this.filterOptions());
                    }
                }

                toggleOpen() {
                    this.isOpen = !this.isOpen;
                    this.element.classList.toggle('on', this.isOpen);

                    if (this.isOpen) {
                        document.addEventListener('click', this.handleDocumentClick);
                    } else {
                        document.removeEventListener('click', this.handleDocumentClick);
                    }
                }

                handleDocumentClick(event) {
                    if (!this.element.contains(event.target)) {
                        this.isOpen = false;
                        this.element.classList.remove('on');
                        document.removeEventListener('click', this.handleDocumentClick);
                    }
                }

                setLabel(text) {
                    if (this.label) {
                        this.label.textContent = text;
                    }
                }

                updateToggleAllText(text) {
                    if (this.toggleAllButton) {
                        this.toggleAllButton.textContent = text;
                    }
                }

                updateStatus() {
                    const checked = this.checkboxes.filter((checkbox) => checkbox.checked);
                    this.areAllChecked = checked.length > 0 && checked.length === this.checkboxes.length;

                    if (checked.length === 0) {
                        this.setLabel(this.placeholder);
                        this.updateToggleAllText('Check All');
                        return;
                    }

                    if (checked.length === 1) {
                        const label = checked[0].closest('label');
                        const text = label ? label.textContent.trim() : checked[0].value;
                        this.setLabel(text);
                    } else if (this.areAllChecked) {
                        this.setLabel('All Selected');
                    } else {
                        this.setLabel(`${checked.length} Selected`);
                    }

                    this.updateToggleAllText(this.areAllChecked ? 'Uncheck All' : 'Check All');
                }

                toggleAll(forceCheck = false) {
                    const targetState = forceCheck || !this.areAllChecked;
                    this.checkboxes.forEach((checkbox) => {
                        checkbox.checked = targetState;
                    });
                    this.areAllChecked = targetState;
                    this.updateStatus();
                }

                filterOptions() {
                    if (!this.searchInput) {
                        return;
                    }

                    const term = this.searchInput.value.trim().toLowerCase();

                    this.optionLabels.forEach((label) => {
                        const text = label.textContent?.toLowerCase() ?? '';
                        label.style.display = text.includes(term) ? 'flex' : 'none';
                    });
                }
            }

            let applyAirlineFilters = () => {};

            const scrollTargetId = @json($scrollTarget);
            const scrollToResults = @json($searchPerformed);
            document.addEventListener('DOMContentLoaded', () => {
                const tripTypeInput = document.getElementById('trip_type_input');
                const tripTypeButtons = document.querySelectorAll('.trip-type-btn');
                const returnWrapper = document.getElementById('return_date_wrapper');
                const returnInput = document.getElementById('return_date');
                const swapRoutesButton = document.getElementById('swap_routes');
                const sortInput = document.getElementById('sortInput');
                const flexOptionsContainer = document.querySelector('[data-flex-options]');
                const flexButtons = Array.from(document.querySelectorAll('[data-flex-option]'));
                const bucketContainers = Array.from(document.querySelectorAll('[data-offer-bucket]'));
                let currentFlexOffset = Number(flexOptionsContainer?.dataset.defaultOffset ?? '0');
                const highlightClasses = ['ring-2', 'ring-indigo-400', 'ring-offset-2'];
                let highlightedCard = null;

                const clearHighlightedCard = () => {
                    if (highlightedCard) {
                        highlightedCard.classList.remove(...highlightClasses);
                        highlightedCard = null;
                    }
                };

                const highlightOfferCard = (offerId) => {
                    if (!offerId) {
                        clearHighlightedCard();
                        return;
                    }

                    const cards = Array.from(document.querySelectorAll('[data-offer-card]'));
                    const target = cards.find((card) => {
                        const matchesId = card.dataset.offerId === offerId;
                        const belongsToBucket = Number(card.dataset.flexOffset ?? '0') === currentFlexOffset;
                        const isVisible = !card.classList.contains('hidden') && !card.closest('[data-offer-bucket]')?.classList.contains('hidden');

                        return matchesId && belongsToBucket && isVisible;
                    });

                    clearHighlightedCard();

                    if (!target) {
                        console.warn('Unable to highlight offer card. It may be hidden by filters.');
                        return;
                    }

                    highlightedCard = target;
                    highlightedCard.classList.add(...highlightClasses);
                    highlightedCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                };

                const updateFlexButtons = () => {
                    if (flexButtons.length === 0) {
                        return;
                    }

                    flexButtons.forEach((button) => {
                        const offset = Number(button.dataset.flexOffset ?? '0');
                        const activeClasses = (button.dataset.activeClass || '').split(' ').filter(Boolean);
                        const inactiveClasses = (button.dataset.inactiveClass || '').split(' ').filter(Boolean);
                        const isActive = offset === currentFlexOffset;

                        if (activeClasses.length > 0 || inactiveClasses.length > 0) {
                            button.classList.remove(...activeClasses, ...inactiveClasses);
                            button.classList.add(...(isActive ? activeClasses : inactiveClasses));
                        }

                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                };

                const updateBucketVisibility = () => {
                    if (bucketContainers.length === 0) {
                        return;
                    }

                    bucketContainers.forEach((container) => {
                        const offset = Number(container.dataset.flexOffset ?? '0');
                        container.classList.toggle('hidden', offset !== currentFlexOffset);
                    });
                };

                const setFlexOffset = (offset) => {
                    if (!Number.isFinite(offset)) {
                        return;
                    }

                    if (currentFlexOffset === offset) {
                        updateFlexButtons();
                        updateBucketVisibility();
                        clearHighlightedCard();
                        return;
                    }

                    currentFlexOffset = offset;
                    updateFlexButtons();
                    updateBucketVisibility();
                    clearHighlightedCard();
                    applyAirlineFilters();
                };

                updateFlexButtons();
                updateBucketVisibility();

                flexButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const offset = Number(button.dataset.flexOffset ?? '0');
                        setFlexOffset(offset);
                    });
                });

                const setActiveTripType = (value) => {
                    if (!tripTypeInput) {
                        return;
                    }

                    tripTypeInput.value = value;

                    tripTypeButtons.forEach((button) => {
                        const isActive = button.dataset.tripType === value;
                        const activeClasses = ['bg-black', 'text-white', 'shadow-md', 'border', 'border-black'];
                        const inactiveClasses = ['bg-white', 'text-slate-600', 'border', 'border-transparent', 'hover:text-slate-900', 'hover:bg-slate-50'];

                        if (button.disabled) {
                            button.classList.remove(...activeClasses);
                            button.classList.add('bg-white', 'text-slate-400', 'border', 'border-transparent', 'opacity-60');
                            return;
                        }

                        if (isActive) {
                            button.classList.remove(...inactiveClasses);
                            button.classList.add(...activeClasses);
                        } else {
                            button.classList.remove(...activeClasses, 'opacity-60');
                            button.classList.add(...inactiveClasses);
                        }
                    });

                    if (returnWrapper && returnInput) {
                        const hideReturn = value === 'one_way';
                        returnWrapper.classList.toggle('hidden', hideReturn);
                        returnInput.disabled = hideReturn;
                        if (hideReturn) {
                            returnInput.value = '';
                        }
                    }
                };

                const initialTripType = tripTypeInput ? tripTypeInput.value : 'return';
                setActiveTripType(initialTripType || 'return');

                tripTypeButtons.forEach((button) => {
                    if (button.disabled) {
                        return;
                    }

                    button.addEventListener('click', () => {
                        const tripType = button.dataset.tripType || 'return';
                        setActiveTripType(tripType);
                    });
                });

                if (swapRoutesButton) {
                    swapRoutesButton.addEventListener('click', () => {
                        if (window.AirportSelectorManager) {
                            window.AirportSelectorManager.swap('origin', 'destination');
                        }
                    });
                }

                const searchForm = document.getElementById('flight-search-form');
                if (searchForm) {
                    searchForm.addEventListener('reset', () => {
                        window.setTimeout(() => {
                            const defaultTrip = tripTypeInput ? tripTypeInput.defaultValue || 'return' : 'return';
                            setActiveTripType(defaultTrip);
                            if (window.AirportSelectorManager) {
                                window.AirportSelectorManager.refreshAll();
                            }
                            if (sortInput) {
                                sortInput.value = sortInput.defaultValue || 'best';
                            }
                        }, 0);
                    });
                }

                if (scrollTargetId) {
                    const target = document.getElementById(scrollTargetId);

                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        target.classList.add('ring-2', 'ring-emerald-400', 'ring-offset-2', 'transition');

                        window.setTimeout(() => {
                            target.classList.remove('ring-2', 'ring-emerald-400', 'ring-offset-2');
                        }, 2000);
                    }
                } else if (scrollToResults) {
                    const resultsAnchor = document.querySelector('[data-results-anchor]');
                    if (resultsAnchor) {
                        resultsAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                document.querySelectorAll('[data-control="checkbox-dropdown"]').forEach((element) => {
                    new CheckboxDropdown(element);
                });

                document.querySelectorAll('[data-airline-search]').forEach((input) => {
                    const wrapper = input.closest('[data-airline-filter-wrapper]');
                    const list = wrapper ? wrapper.querySelector('[data-airline-list]') : null;
                    if (!list) {
                        return;
                    }

                    const items = Array.from(list.querySelectorAll('label')).map((label) => ({
                        label,
                        text: (label.textContent || '').trim().toLowerCase(),
                    }));

                    const render = (term) => {
                        const normalized = term.trim().toLowerCase();
                        const matches = normalized === ''
                            ? items
                            : items.filter(({ text }) => text.includes(normalized));

                        const sorted = matches.slice().sort((a, b) => a.text.localeCompare(b.text));

                        list.innerHTML = '';
                        sorted.forEach(({ label }) => list.appendChild(label));
                    };

                    input.addEventListener('input', () => render(input.value));
                });

                const setupAirlineFilters = () => {
                    const filterCheckboxes = Array.from(document.querySelectorAll('[data-airline-filter]'));
                    const interlineSelect = document.getElementById('interline');
                    if (filterCheckboxes.length === 0 && !interlineSelect) {
                        return;
                    }

                    const offerCards = Array.from(document.querySelectorAll('[data-offer-card]'));
                    const summaryTarget = document.querySelector('[data-airline-filter-summary]');
                    const summaryDefault = summaryTarget?.dataset.defaultText || 'Showing all airlines';
                    const selectionCount = document.querySelector('[data-airline-selection-count]');
                    const countTarget = document.querySelector('[data-offer-count]');
                    const statsContainer = document.querySelector('[data-offer-stats]');
                    const statsCards = statsContainer ? Array.from(statsContainer.querySelectorAll('[data-stat-card]')) : [];
                    const emptyState = document.querySelector('[data-filter-no-results]');

                    const formatAmount = (value) => {
                        const number = Number(value);
                        if (!Number.isFinite(number)) {
                            return value;
                        }

                        return number.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                    };

                    const updateStats = (visibleOffers) => {
                        if (!statsContainer) {
                            return;
                        }

                        if (visibleOffers.length === 0) {
                            statsContainer.classList.add('hidden');
                            return;
                        }

                        statsContainer.classList.remove('hidden');

                        const pricedOffers = visibleOffers.filter((offer) => Number.isFinite(offer.price));

                        if (pricedOffers.length === 0) {
                            statsContainer.classList.add('hidden');
                            return;
                        }

                        const sorted = pricedOffers
                            .slice()
                            .sort((a, b) => (a.price ?? Infinity) - (b.price ?? Infinity));
                        const best = sorted[0] ?? null;
                        const next = sorted[1] ?? null;
                        const stats = {
                            best,
                            cheapest: best,
                            next_best: next ?? best,
                        };

                        statsCards.forEach((card) => {
                            const type = card.dataset.statType;
                            const stat = type ? stats[type] : null;

                            if (!stat) {
                                card.classList.add('hidden');
                                return;
                            }

                            card.classList.remove('hidden');

                            const valueTarget = card.querySelector('[data-stat-value]');
                            if (valueTarget) {
                                const rawAmount = Number(stat.price ?? valueTarget.dataset.amount ?? 0);
                                const amount = Number.isFinite(rawAmount) ? rawAmount : 0;
                                const currency = stat.currency || valueTarget.dataset.currency || '';
                                valueTarget.dataset.amount = String(amount);
                                valueTarget.dataset.currency = currency;
                                const formatted = formatAmount(amount);
                                valueTarget.textContent = currency ? `${currency} ${formatted}` : formatted;
                            }
                        });
                    };

                    const updateHistory = (codes, interlineVal) => {
                        if (!window.history || typeof window.history.replaceState !== 'function') {
                            return;
                        }

                        try {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('selected_airlines[]');
                            codes.forEach((code) => url.searchParams.append('selected_airlines[]', code));
                            if (interlineVal) {
                                url.searchParams.set('interline', interlineVal);
                            } else {
                                url.searchParams.delete('interline');
                            }
                            window.history.replaceState({}, document.title, url.toString());
                        } catch (error) {
                            // Ignore history errors on older browsers.
                        }
                    };

                    const getSelectedCodes = () => filterCheckboxes
                        .filter((checkbox) => checkbox.checked)
                        .map((checkbox) => (checkbox.value || '').toUpperCase())
                        .filter((code) => code !== '');

                    const applyFilters = () => {
                        const selectedCodes = getSelectedCodes();
                        const interlineValue = (interlineSelect?.value || '').toUpperCase();
                        const visibleOffers = [];
                        let bucketHasOffers = false;

                        offerCards.forEach((card) => {
                            const cardOffset = Number(card.dataset.flexOffset ?? '0');
                            const matchesBucket = cardOffset === currentFlexOffset;
                            if (matchesBucket) {
                                bucketHasOffers = true;
                            }
                            const code = (card.dataset.airlineCode || '').toUpperCase();
                            const cardInterline = (card.dataset.interlineType || '').toUpperCase();
                            const matchesAirline = selectedCodes.length === 0 || selectedCodes.includes(code);
                            const matchesInterline = interlineValue === '' || interlineValue === cardInterline;
                            const shouldShow = matchesBucket && matchesAirline && matchesInterline;
                            card.classList.toggle('hidden', !shouldShow);

                            if (shouldShow) {
                                const rawPrice = Number(card.dataset.offerPrice ?? '0');
                                const price = Number.isFinite(rawPrice) ? rawPrice : null;
                                const currency = card.dataset.offerCurrency || '';
                                visibleOffers.push({ price, currency });
                            } else if (highlightedCard === card) {
                                clearHighlightedCard();
                            }
                        });

                        if (countTarget) {
                            const count = visibleOffers.length;
                            const label = count === 1 ? 'result' : 'results';
                            const sortLabel = countTarget.dataset.sortLabel || 'Best';
                            countTarget.textContent = `${count} ${label} sorted by ${sortLabel}`;
                        }

                        if (summaryTarget) {
                            const interlineLabels = {
                                Y: 'Different marketing carriers',
                                N: 'Plating carrier only',
                                D: 'Only other than plating carrier',
                            };
                            const summaryParts = [];
                            if (selectedCodes.length > 0) {
                                summaryParts.push(`Airlines: ${selectedCodes.join(', ')}`);
                            }
                            if (interlineValue && interlineLabels[interlineValue]) {
                                summaryParts.push(`Interline: ${interlineLabels[interlineValue]}`);
                            }

                            summaryTarget.textContent = summaryParts.length > 0
                                ? summaryParts.join(' • ')
                                : summaryDefault;
                        }

                        if (selectionCount) {
                            selectionCount.textContent = (selectedCodes.length === 0 && interlineValue === '')
                                ? 'All'
                                : 'Filtered';
                        }

                        if (emptyState) {
                            const showEmpty = bucketHasOffers && visibleOffers.length === 0;
                            emptyState.classList.toggle('hidden', !showEmpty);
                        }

                        updateStats(visibleOffers);
                        updateHistory(selectedCodes, interlineValue);
                    };

                    filterCheckboxes.forEach((checkbox) => {
                        checkbox.addEventListener('change', applyFilters);
                    });

                    if (interlineSelect) {
                        interlineSelect.addEventListener('change', applyFilters);
                    }

                    document.querySelectorAll('[data-clear-airline-filters]').forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.preventDefault();
                            filterCheckboxes.forEach((checkbox) => {
                                checkbox.checked = false;
                            });
                            if (interlineSelect) {
                                interlineSelect.value = '';
                            }
                            applyFilters();
                        });
                    });

                    applyAirlineFilters = applyFilters;
                    applyFilters();
                };

                setupAirlineFilters();

                document.querySelectorAll('[data-highlight-offer]').forEach((button) => {
                    button.addEventListener('click', () => {
                        highlightOfferCard(button.dataset.highlightOffer || '');
                    });
                });

            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const orderForm = document.querySelector('[data-ndc-order-form]');
                const statusContainer = document.querySelector('[data-ndc-order-status]');
                const statusIdTarget = document.querySelector('[data-ndc-order-status-id]');
                const paymentButtons = document.querySelectorAll('[data-payment-button]');
                const paymentLockedMessage = document.querySelector('[data-payment-locked-message]');
                const collectPaymentSection = document.querySelector('[data-collect-payment-section]');

                if (!orderForm || typeof window.fetch !== 'function') {
                    return;
                }

                const submitButton = orderForm.querySelector('[data-ndc-order-submit]');
                const errorContainer = orderForm.querySelector('[data-ndc-order-error]');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                orderForm.addEventListener('submit', async (event) => {
                    if (!submitButton) {
                        return;
                    }

                    event.preventDefault();

                    const originalText = submitButton.dataset.originalText || submitButton.textContent.trim();
                    submitButton.dataset.originalText = originalText;
                    submitButton.disabled = true;
                    submitButton.textContent = submitButton.dataset.loadingText || 'Processing...';

                    if (errorContainer) {
                        errorContainer.textContent = '';
                    }

                    try {
                        const response = await fetch(orderForm.action, {
                            method: orderForm.getAttribute('method') || 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                            },
                            body: new FormData(orderForm),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || !data.success) {
                            const message = data.message || 'Unable to create TravelNDC order. Please try again.';
                            if (errorContainer) {
                                errorContainer.textContent = message;
                            }
                            return;
                        }

                        orderForm.classList.add('hidden');

                        if (statusContainer) {
                            if (data.order_id && statusIdTarget) {
                                statusIdTarget.textContent = data.order_id;
                            }
                            statusContainer.classList.remove('hidden');
                        }

                        if (collectPaymentSection) {
                            collectPaymentSection.classList.remove('hidden');
                        }

                        if (paymentLockedMessage) {
                            paymentLockedMessage.remove();
                        }

                        paymentButtons.forEach((button) => {
                            if (button instanceof HTMLButtonElement) {
                                button.disabled = false;
                            }
                        });
                    } catch (error) {
                        if (errorContainer) {
                            errorContainer.textContent = 'Network error while creating TravelNDC order. Please try again.';
                        }
                        console.error('TravelNDC order creation error:', error);
                    } finally {
                        submitButton.disabled = false;
                        submitButton.textContent = submitButton.dataset.originalText || originalText;
                    }
                });
            });
        </script>
        <script>
            (function () {
                if (window.__stripeCheckoutInitialized) {
                    return;
                }

                window.__stripeCheckoutInitialized = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                const bindStripeButtons = () => {
                    const stripeButtons = document.querySelectorAll('[data-stripe-button]');

                    stripeButtons.forEach((button) => {
                        if (button.dataset.stripeBound === 'true') {
                            return;
                        }

                        button.dataset.stripeBound = 'true';

                        button.addEventListener('click', async (event) => {
                            if (event.defaultPrevented) {
                                return;
                            }

                            const form = button.closest('form');

                            if (!form) {
                                return;
                            }

                            event.preventDefault();

                            const errorContainer = form.querySelector('[data-stripe-error]');
                            const originalLabel = button.dataset.originalText || button.textContent.trim();
                            const loadingLabel = button.dataset.loadingText || 'Processing...';

                            button.dataset.originalText = originalLabel;
                            button.disabled = true;
                            button.textContent = loadingLabel;

                            if (errorContainer) {
                                errorContainer.textContent = '';
                            }

                            try {
                                const targetUrl = button.dataset.stripeUrl || button.getAttribute('formaction') || form.action;

                                if (!targetUrl) {
                                    throw new Error('Stripe checkout URL is missing.');
                                }

                                const response = await fetch(targetUrl, {
                                    method: form.getAttribute('method') || 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                    },
                                    credentials: 'same-origin',
                                    body: new FormData(form),
                                });

                                const data = await response.json().catch(() => ({}));

                                if (response.ok && data.redirect_url) {
                                    window.location.href = data.redirect_url;
                                    return;
                                }

                                const message =
                                    (data.errors && data.errors.checkout && data.errors.checkout[0]) ||
                                    data.message ||
                                    'Unable to start Stripe checkout. Please try again later.';

                                if (errorContainer) {
                                    errorContainer.textContent = message;
                                } else {
                                    console.error('Stripe checkout error:', message);
                                }
                            } catch (error) {
                                if (errorContainer) {
                                    errorContainer.textContent = 'Network error while contacting Stripe. Please try again.';
                                }

                                console.error('Stripe checkout network error:', error);
                            } finally {
                                button.disabled = false;
                                button.textContent = button.dataset.originalText || originalLabel;
                            }
                        });
                    });
                };

                document.addEventListener('DOMContentLoaded', bindStripeButtons);

                if (document.readyState !== 'loading') {
                    bindStripeButtons();
                }
            })();
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const routeNodes = Array.from(document.querySelectorAll('.segment-route'));
                if (!routeNodes.length) return;

                let airportMap = null;
                const loadAirports = async () => {
                    if (airportMap) return airportMap;
                    try {
                        const resp = await fetch('/airports.json');
                        const data = await resp.json();
                        airportMap = new Map();
                        (Array.isArray(data) ? data : []).forEach((a) => {
                            const code = (a.iata_code || a.iata || '').toUpperCase();
                            if (!code) return;
                            const city = a.city || '';
                            const country = a.country || '';
                            const name = a.name || '';
                            const display = city && country ? `${city}, ${country} (${code})` : name ? `${name} (${code})` : code;
                            airportMap.set(code, display);
                        });
                    } catch (e) {
                        console.warn('Unable to load airports.json for display names', e);
                        airportMap = new Map();
                    }
                    return airportMap;
                };

                loadAirports().then((map) => {
                    routeNodes.forEach((node) => {
                        const origin = (node.dataset.origin || '').toUpperCase();
                        const dest = (node.dataset.destination || '').toUpperCase();
                        const originText = map.get(origin) || origin || '---';
                        const destText = map.get(dest) || dest || '---';
                        node.textContent = `${originText} → ${destText}`;
                    });
                });
            });
        </script>
</x-app-layout>
