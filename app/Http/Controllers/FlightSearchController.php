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
                        $baseAmount = round((float) ($pricing['base_amount'] ?? 0), 2);
                        $taxAmount = round(
                            (float) ($pricing['tax_amount'] ?? (($pricing['total_amount'] ?? 0) - $baseAmount)),
                            2
                        );
                        $taxAmount = $taxAmount < 0 ? 0.0 : $taxAmount;

                        $commissionBreakdown = $this->commissionService->pricingForAirline($carrier, $baseAmount);
                        $payableTotal = round($baseAmount + $taxAmount + $commissionBreakdown['commission_amount'], 2);

                        $offer['pricing']['base_amount'] = $baseAmount;
                        $offer['pricing']['tax_amount'] = $taxAmount;
                        $offer['pricing']['total_amount'] = round((float) ($pricing['total_amount'] ?? $baseAmount + $taxAmount), 2);
                        $offer['pricing']['commission'] = $commissionBreakdown;
                        $offer['pricing']['markup'] = $commissionBreakdown;
                        $offer['pricing']['components'] = [
                            'base_fare' => $baseAmount,
                            'taxes' => $taxAmount,
                            'commission' => $commissionBreakdown['commission_amount'],
                        ];
                        $offer['pricing']['payable_total'] = $payableTotal;
                        $offer['pricing']['display_total'] = $payableTotal;

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
