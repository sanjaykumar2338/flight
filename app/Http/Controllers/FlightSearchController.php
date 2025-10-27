<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\FlightSearchData;
use App\Http\Requests\FlightSearchRequest;
use App\Services\Pricing\CommissionService;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;

class FlightSearchController extends Controller
{
    public function __construct(
        private readonly TravelNdcService $travelNdcService,
        private readonly CommissionService $commissionService
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
                    ->map(function (array $offer) {
                        $pricing = Arr::get($offer, 'pricing', []);
                        $carrier = trim((string) Arr::get($offer, 'primary_carrier', Arr::get($offer, 'owner', '')));

                        $totals = $this->commissionService->pricingForAirline(
                            $carrier,
                            (float) ($pricing['total_amount'] ?? $pricing['base_amount'] ?? 0)
                        );

                        $offer['pricing']['base_amount'] = round((float) ($pricing['base_amount'] ?? 0), 2);
                        $offer['pricing']['tax_amount'] = round((float) ($pricing['tax_amount'] ?? 0), 2);
                        $offer['pricing']['total_amount'] = round((float) ($pricing['total_amount'] ?? 0), 2);
                        $offer['pricing']['markup'] = $totals;
                        $offer['pricing']['display_total'] = $totals['display_amount'];

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
        $pricedBooking = $pricedBookingId ? Booking::find($pricedBookingId) : null;

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
        ]);
    }
}
