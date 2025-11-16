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

        if ($request->filled('ref')) {
            session(['ref' => trim((string) $request->input('ref'))]);
        }

        $flexibleDays = $request->flexibleDays();
        $selectedAirlines = $request->airlineFilters();

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
            'flexibleDays' => $flexibleDays,
            'selectedAirlines' => $selectedAirlines,
            'availableAirlines' => $availableAirlines,
            'offers' => $offers,
            'errorMessage' => $errorMessage,
            'pricedOffer' => $pricedOffer,
            'pricedBooking' => $pricedBooking,
            'bookingCreated' => $bookingCreated,
            'videcomHold' => $videcomHold,
            'scrollTo' => $scrollTo,
            'airports' => $this->airportOptions(),
        ]);
    }

    private function airportOptions(): array
    {
        $airports = config('airports', []);

        return collect($airports)
            ->map(fn ($name, $code) => [
                'code' => strtoupper($code),
                'name' => $name,
            ])
            ->values()
            ->all();
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
}
