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

        if ($request->filled('ref')) {
            session(['ref' => trim((string) $request->input('ref'))]);
        }

        $flexibleDays = $request->flexibleDays();
        $selectedAirlines = $request->airlineFilters();
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

                $offers = collect($searchResults['offers'])
                    ->when(!empty($selectedAirlines), function ($collection) use ($selectedAirlines) {
                        $filters = array_map('strtoupper', $selectedAirlines);

                        return $collection->filter(function ($offer) use ($filters) {
                            return in_array(strtoupper(Arr::get($offer, 'primary_carrier')), $filters, true);
                        });
                    })
                    ->map(function (array $offer) use ($searchData, $passengerSummary) {
                        $pricing = Arr::get($offer, 'pricing', []);
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

                        return $offer;
                    })
                    ->values();

                $dateRangeSummaries = $this->buildDateRangeSummaries(
                    $searchData,
                    $offers,
                    $flexibleDays,
                    $request,
                    $currencyFallback
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
            ],
            'selectedAirlines' => $selectedAirlines,
            'airlineOptions' => $airlineOptions,
            'airlineLookup' => $airlineLookup->all(),
            'availableAirlines' => $availableAirlines,
            'offers' => $offers,
            'errorMessage' => $errorMessage,
            'dateRangeSummaries' => $dateRangeSummaries,
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
        string $currencyFallback
    ): Collection {
        $baseDeparture = $searchData->departureDate->copy();
        $baseReturn = $searchData->returnDate?->copy();
        $queryBase = collect($request->query())
            ->except(['departure_date', 'return_date', 'page'])
            ->all();

        return collect(range(-$flexibleDays, $flexibleDays))
            ->map(function (int $offset) use ($baseDeparture, $baseReturn, $offers, $queryBase, $currencyFallback) {
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
                    'is_selected' => $offset === 0,
                ];
            })
            ->values();
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

        return [
            'carrier' => strtoupper($carrier),
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
