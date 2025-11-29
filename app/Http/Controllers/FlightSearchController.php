<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\FlightSearchData;
use App\Http\Requests\FlightSearchRequest;
use App\Services\Pricing\PricingService;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FlightSearchController extends Controller
{
    public function __construct(
        private readonly TravelNdcService $travelNdcService,
        private readonly PricingService $pricingService
    ) {
    }

    public function index(FlightSearchRequest $request): View
    {
        $offers = collect();
        $availableAirlines = [];
        $errorMessage = null;
        $currencyFallback = $this->detectCurrency($request);
        $searchData = null;
        $dateRangeSummaries = collect();
        $summaryOffers = [
            'best' => null,
            'cheapest' => null,
            'next_best' => null,
        ];
        $sortOption = $this->normalizeSortOption($request->input('sort'));
        $flexibleBuckets = collect();
        $activeFlexOffset = 0;

        if ($request->filled('ref')) {
            session(['ref' => trim((string) $request->input('ref'))]);
        }

        $flexibleDays = $request->flexibleDays();
        $selectedAirlines = $request->airlineFilters();
        $interlineFilter = strtoupper((string) $request->input('interline', ''));
        $interlineFilter = in_array($interlineFilter, ['Y', 'N', 'D'], true) ? $interlineFilter : '';
        $airlineLookup = collect(config('airlines', []))
            ->mapWithKeys(fn ($name, $code) => [strtoupper($code) => (string) $name]);
        $airlineOptions = $airlineLookup
            ->map(fn ($name, $code) => [
                'code' => $code,
                'name' => $name,
                'label' => sprintf('%s – %s', $code, $name),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        if ($request->hasSearchCriteria()) {
            try {
                $searchData = FlightSearchData::fromArray($request->validated());
                $searchResults = $this->travelNdcService->searchFlights($searchData, $flexibleDays, $selectedAirlines);
                $passengerSummary = $this->buildPassengerSummary($searchData);

                $availableAirlines = collect($searchResults['airlines'])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $allOffers = collect($searchResults['offers'])
                    ->map(function (array $offer) use ($searchData, $passengerSummary) {
                        $pricing = Arr::get($offer, 'pricing', []);
                        $offer['segments'] = $this->hydrateSegments($offer['segments'] ?? []);
                        $carrier = trim((string) Arr::get($offer, 'primary_carrier', Arr::get($offer, 'owner', '')));
                        $currency = $offer['currency'] ?? ($pricing['currency'] ?? config('travelndc.currency', 'USD'));
                        $baseAmount = round((float) ($pricing['base_amount'] ?? 0), 2);
                        $taxAmount = round(
                            (float) ($pricing['tax_amount'] ?? (($pricing['total_amount'] ?? 0) - $baseAmount)),
                            2
                        );
                        $taxAmount = $taxAmount < 0 ? 0.0 : $taxAmount;

                        $context = $this->buildPricingContext($offer, $searchData, $carrier, $currency, $taxAmount);
                        $pricingData = $this->pricingService->calculate(
                            $offer['segments'] ?? [],
                            $passengerSummary,
                            $baseAmount,
                            $taxAmount,
                            $currency,
                            $context
                        );

                        $offer['pricing'] = array_merge([
                            'base_amount' => $baseAmount,
                            'tax_amount' => $taxAmount,
                            'total_amount' => round((float) ($pricing['total_amount'] ?? $baseAmount + $taxAmount), 2),
                        ], $pricingData, [
                            'currency' => $currency,
                            'context' => $context,
                            'passengers' => $passengerSummary,
                        ]);

                        $offer['passenger_summary'] = $passengerSummary;
                        $offer['pricing_context'] = $context;
                        $offer['interline_type'] = $this->determineInterlineType($offer, $carrier);

                        return $offer;
                    })
                    ->filter(fn (array $offer) => $this->matchesInterlineSelection($offer['interline_type'] ?? '', $interlineFilter))
                    ->values();

                $flexibleBuckets = $this->buildFlexibleBuckets($allOffers, $sortOption);
                $activeFlexOffset = $this->determineActiveFlexOffset($flexibleBuckets);
                $currentBucket = $flexibleBuckets->get($activeFlexOffset, [
                    'offers' => collect(),
                    'summary' => $summaryOffers,
                ]);
                $offers = $currentBucket['offers'] ?? collect();
                $summaryOffers = $currentBucket['summary'] ?? $summaryOffers;

                $dateRangeSummaries = $this->buildDateRangeSummaries(
                    $searchData,
                    $allOffers,
                    $flexibleDays,
                    $request,
                    $currencyFallback,
                    $activeFlexOffset
                );
            } catch (TravelNdcException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        $pricedOffer = session()->pull('pricedOffer');
        $pricedBookingId = session()->pull('bookingId');
        $bookingCreated = session()->pull('bookingCreated');
        $videcomHold = session()->pull('videcomHold');
        $pricedBooking = $pricedBookingId ? Booking::find($pricedBookingId) : null;
        $scrollTo = session()->pull('scrollTo');

        return view('flights.search', [
            'searchPerformed' => $request->hasSearchCriteria(),
            'searchParams' => [
                'origin' => $request->input('origin'),
                'destination' => $request->input('destination'),
                'departure_date' => $request->input('departure_date'),
                'return_date' => $request->input('return_date'),
                'adults' => $request->input('adults', 1),
                'children' => $request->input('children', 0),
                'infants' => $request->input('infants', 0),
                'cabin_class' => $request->input('cabin_class', 'ECONOMY'),
                'sort' => $sortOption,
                'interline' => $interlineFilter,
            ],
            'selectedAirlines' => $selectedAirlines,
            'airlineOptions' => $airlineOptions,
            'airlineLookup' => $airlineLookup->all(),
            'availableAirlines' => $availableAirlines,
            'offers' => $offers,
            'errorMessage' => $errorMessage,
            'dateRangeSummaries' => $dateRangeSummaries,
            'summaryOffers' => $summaryOffers,
            'sortOption' => $sortOption,
            'flexibleBuckets' => $flexibleBuckets,
            'activeFlexOffset' => $activeFlexOffset,
            'pricedOffer' => $pricedOffer,
            'pricedBooking' => $pricedBooking,
            'bookingCreated' => $bookingCreated,
            'videcomHold' => $videcomHold,
            'scrollTo' => $scrollTo,
            'currencyFallback' => $currencyFallback,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildDateRangeSummaries(
        FlightSearchData $searchData,
        Collection $offers,
        int $flexibleDays,
        FlightSearchRequest $request,
        string $currencyFallback,
        int $activeOffset = 0
    ): Collection {
        $baseDeparture = $searchData->departureDate->copy();
        $baseReturn = $searchData->returnDate?->copy();
        $queryBase = collect($request->query())
            ->except(['departure_date', 'return_date', 'page'])
            ->all();

        return collect(range(-$flexibleDays, $flexibleDays))
            ->map(function (int $offset) use ($baseDeparture, $baseReturn, $offers, $queryBase, $currencyFallback, $activeOffset) {
                $start = $baseDeparture->copy()->addDays($offset);
                $end = $baseReturn ? $baseReturn->copy()->addDays($offset) : null;

                $matching = $offers->filter(fn ($offer) => (int) ($offer['day_offset'] ?? 0) === $offset);
                $price = $matching
                    ->map(fn ($offer) => (float) Arr::get($offer, 'pricing.payable_total', Arr::get($offer, 'pricing.total_amount', 0)))
                    ->filter(fn ($amount) => $amount > 0)
                    ->sort()
                    ->first();
                $currency = $matching
                    ->map(fn ($offer) => Arr::get($offer, 'pricing.currency'))
                    ->filter()
                    ->first() ?? $currencyFallback;

                $query = array_merge($queryBase, [
                    'departure_date' => $start->toDateString(),
                ]);

                if ($end) {
                    $query['return_date'] = $end->toDateString();
                } else {
                    $query['return_date'] = null;
                }

                return [
                    'offset' => $offset,
                    'start' => $start,
                    'end' => $end,
                    'price' => $price,
                    'currency' => $currency,
                    'count' => $matching->count(),
                    'url' => route('flights.search', array_filter($query, fn ($value) => $value !== null && $value !== '')),
                    'is_selected' => $offset === $activeOffset,
                ];
            })
            ->values();
    }

    private function buildFlexibleBuckets(Collection $offers, string $sortOption): Collection
    {
        return $offers
            ->groupBy(fn ($offer) => (int) ($offer['day_offset'] ?? 0))
            ->sortKeys()
            ->map(function (Collection $bucket) use ($sortOption) {
                [$summary, $sortedOffers] = $this->prepareSummaryOffers($bucket);

                return [
                    'summary' => $summary,
                    'offers' => $this->applySortOption($sortedOffers, $sortOption),
                ];
            });
    }

    private function determineActiveFlexOffset(Collection $flexibleBuckets): int
    {
        if ($flexibleBuckets->has(0)) {
            return 0;
        }

        $firstKey = $flexibleBuckets->keys()->first();

        return $firstKey !== null ? (int) $firstKey : 0;
    }

    /**
     * @return array{0: array{best: array<string, mixed>|null, cheapest: array<string, mixed>|null, next_best: array<string, mixed>|null}, 1: Collection<int, array<string, mixed>>}
     */
    private function prepareSummaryOffers(Collection $offers): array
    {
        $sorted = $offers
            ->sortBy(fn ($offer) => $this->getOfferPayableTotal($offer))
            ->values();

        $cheapest = $sorted->first();
        $nextBest = $sorted->get(1);

        return [
            [
                'best' => $cheapest,
                'cheapest' => $cheapest,
                'next_best' => $nextBest,
            ],
            $sorted,
        ];
    }

    private function applySortOption(Collection $offers, string $sortOption): Collection
    {
        if ($offers->isEmpty()) {
            return $offers;
        }

        if ($sortOption === 'next_best') {
            $nextBest = $offers->get(1);

            if (!$nextBest) {
                return $offers;
            }

            $remaining = $offers
                ->reject(fn ($offer, $index) => $index === 1)
                ->values();

            return collect([$nextBest])->merge($remaining)->values();
        }

        return $offers;
    }

    private function getOfferPayableTotal(array $offer): float
    {
        $pricing = $offer['pricing'] ?? [];

        return (float) ($pricing['payable_total'] ?? $pricing['total_amount'] ?? 0.0);
    }

    private function normalizeSortOption(?string $value): string
    {
        $normalized = strtolower((string) $value);
        $allowed = ['best', 'cheapest', 'next_best'];

        return in_array($normalized, $allowed, true) ? $normalized : 'best';
    }

    private function buildPassengerSummary(FlightSearchData $searchData): array
    {
        $types = [
            'ADT' => max(0, (int) $searchData->adults),
            'CHD' => max(0, (int) $searchData->children),
            'INF' => max(0, (int) $searchData->infants),
        ];

        return [
            'types' => $types,
            'list' => array_keys(array_filter($types, fn ($count) => $count > 0)),
            'total' => array_sum($types),
        ];
    }

    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    private function buildPricingContext(array $offer, FlightSearchData $searchData, string $carrier, string $currency, float $taxAmount): array
    {
        [$origin, $destination] = $this->extractEndpoints($offer, $searchData);
        $segments = $offer['segments'] ?? [];
        $marketingCarriers = $this->extractCarrierCodes($segments, 'marketing_carrier');
        $operatingCarriers = $this->extractCarrierCodes($segments, 'operating_carrier');
        $platingCarrier = strtoupper($offer['owner'] ?? $carrier);
        if (empty($marketingCarriers) && $platingCarrier !== '') {
            $marketingCarriers = [$platingCarrier];
        }
        if (empty($operatingCarriers) && $platingCarrier !== '') {
            $operatingCarriers = [$platingCarrier];
        }

        return [
            'carrier' => strtoupper($carrier),
            'plating_carrier' => $platingCarrier,
            'marketing_carriers' => $marketingCarriers,
            'operating_carriers' => $operatingCarriers,
            'flight_numbers' => $this->extractFlightNumbers($segments),
            'origin' => $origin,
            'destination' => $destination,
            'travel_type' => $searchData->isRoundTrip() ? 'RT' : 'OW',
            'cabin_class' => strtoupper($searchData->cabinClass),
            'fare_type' => 'ANY',
            'promo_code' => null,
            'sales_date' => Carbon::now()->toIso8601String(),
            'departure_date' => $searchData->departureDate->toDateString(),
            'return_date' => $searchData->returnDate?->toDateString(),
            'rbd' => $this->extractRbd($offer),
            'tax_amount' => $taxAmount,
            'currency' => $currency,
        ];
    }

    /**
     * @param array<string, mixed> $offer
     */
    private function extractEndpoints(array $offer, FlightSearchData $searchData): array
    {
        $segments = Arr::get($offer, 'segments', []);
        $firstSegment = is_array($segments) ? Arr::first($segments) : null;
        $lastSegment = is_array($segments) ? Arr::last($segments) : null;

        $origin = strtoupper((string) ($firstSegment['origin'] ?? $searchData->origin));
        $destination = strtoupper((string) ($lastSegment['destination'] ?? $searchData->destination));

        return [$origin, $destination];
    }

    /**
     * @param array<string, mixed> $offer
     */
    private function extractRbd(array $offer): ?string
    {
        $segments = Arr::get($offer, 'segments', []);

        if (is_array($segments) && !empty($segments)) {
            $candidate = $this->findRbdValue($segments[0]);

            if ($candidate) {
                return $candidate;
            }
        }

        $offerItems = Arr::get($offer, 'offer_items', []);

        if (is_array($offerItems) && !empty($offerItems)) {
            $candidate = $this->findRbdValue($offerItems[0]);

            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function findRbdValue(array $data): ?string
    {
        $keys = [
            'rbd',
            'booking_code',
            'booking_class',
            'fare_class',
            'fare_basis',
            'fare_details.rbd',
            'service.fare_basis',
        ];

        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (is_string($value) && $value !== '') {
                return strtoupper(substr($value, 0, 10));
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $segments
     * @return array<int, array<string, mixed>>
     */
    private function hydrateSegments(array $segments): array
    {
        return collect($segments)
            ->map(function ($segment) {
                if (!is_array($segment)) {
                    return [];
                }

                $segment['marketing_carrier'] = isset($segment['marketing_carrier'])
                    ? strtoupper((string) $segment['marketing_carrier'])
                    : null;
                $segment['operating_carrier'] = isset($segment['operating_carrier'])
                    ? strtoupper((string) $segment['operating_carrier'])
                    : null;
                $segment['flight_number'] = $segment['flight_number']
                    ?? $segment['marketing_flight_number']
                    ?? null;
                $segment['flight_number'] = $segment['flight_number']
                    ? strtoupper((string) $segment['flight_number'])
                    : null;

                return $segment;
            })
            ->filter(fn ($segment) => is_array($segment) && !empty($segment))
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $segments
     * @return array<int, string>
     */
    private function extractCarrierCodes(array $segments, string $key): array
    {
        return collect($segments)
            ->map(fn ($segment) => is_array($segment) ? strtoupper(trim((string) ($segment[$key] ?? ''))) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $segments
     * @return array<int, string>
     */
    private function extractFlightNumbers(array $segments): array
    {
        return collect($segments)
            ->map(function ($segment) {
                if (!is_array($segment)) {
                    return null;
                }

                $number = $segment['flight_number'] ?? $segment['marketing_flight_number'] ?? null;

                return $number ? strtoupper(trim((string) $number)) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function determineInterlineType(array $offer, string $carrier): string
    {
        $segments = $offer['segments'] ?? [];
        $marketing = collect($this->extractCarrierCodes($segments, 'marketing_carrier'));
        $platingCarrier = strtoupper($offer['owner'] ?? $carrier);

        if ($marketing->isEmpty() && $platingCarrier !== '') {
            $marketing = collect([$platingCarrier]);
        }

        $uniqueCount = $marketing->count();

        if ($uniqueCount > 1) {
            return 'Y';
        }

        if ($platingCarrier === '') {
            return '';
        }

        if ($uniqueCount > 0 && $marketing->every(fn ($code) => $code === $platingCarrier)) {
            return 'N';
        }

        if ($uniqueCount > 0 && !$marketing->contains($platingCarrier)) {
            return 'D';
        }

        return '';
    }

    private function matchesInterlineSelection(string $offerType, string $selection): bool
    {
        $normalizedOffer = strtoupper(trim($offerType));
        $normalizedSelection = strtoupper(trim($selection));

        if ($normalizedSelection === '') {
            return true;
        }

        return $normalizedOffer === $normalizedSelection;
    }

    private function detectCurrency(FlightSearchRequest $request): string
    {
        $ip = $request->ip();
        $countryCode = null;
        if ($ip && $this->isPublicIp($ip)) {
            $cacheKey = "geoip:{$ip}";

            $countryCode = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($ip) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 4,
                        'ignore_errors' => true,
                    ],
                ]);

                $response = @file_get_contents("http://ip-api.com/php/{$ip}", false, $context);

                if ($response === false) {
                    return null;
                }

                $data = @unserialize($response, ['allowed_classes' => false]);

                if (!is_array($data) || ($data['status'] ?? 'fail') !== 'success') {
                    return null;
                }

                return strtoupper((string) ($data['countryCode'] ?? ''));
            });
        }

        if ($countryCode === null || $countryCode === '') {
            $countryCode = strtoupper((string) $request->header('X-Country-Code', ''));
        }

        if ($countryCode === '') {
            $countryCode = strtoupper((string) $request->server('HTTP_CF_IPCOUNTRY', ''));
        }

        if ($countryCode === '') {
            if ($this->isNigerianAirport($request->input('origin')) || $this->isNigerianAirport($request->input('destination'))) {
                $countryCode = 'NG';
            }
        }

        return $countryCode === 'NG' ? 'NGN' : config('travelndc.currency', 'USD');
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function isNigerianAirport(?string $code): bool
    {
        if (!$code) {
            return false;
        }

        $nigerianCodes = [
            'LOS', 'ABV', 'PHC', 'KAN', 'ENU', 'ILR', 'QOW', 'CBQ', 'KAD', 'YOL',
            'AKR', 'BNI', 'SKO', 'QUO', 'ABB', 'IBA', 'MIU', 'ZAR', 'PHG', 'LOS',
        ];

        return in_array(strtoupper(trim($code)), $nigerianCodes, true);
    }
}
