<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferPriceRequest;
use App\Services\Pricing\CommissionService;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OfferPricingController extends Controller
{
    public function __construct(
        private readonly TravelNdcService $travelNdcService,
        private readonly CommissionService $commissionService
    ) {
    }

    public function __invoke(OfferPriceRequest $request): RedirectResponse
    {
        $payload = $request->decodedOffer();

        try {
            $pricedOffer = $this->travelNdcService->priceOffer($payload);
        } catch (TravelNdcException $exception) {
            return redirect()->back()->withErrors([
                'offer' => $exception->getMessage(),
            ]);
        }

        $ndcPricing = Arr::get($pricedOffer, 'pricing', []);

        $carrier = trim((string) Arr::get($payload, 'primary_carrier', Arr::get($payload, 'owner', '')));
        $totals = $this->commissionService->pricingForAirline(
            $carrier,
            (float) ($ndcPricing['total_amount'] ?? $ndcPricing['base_amount'] ?? 0)
        );

        $pricing = [
            'ndc' => [
                'base_amount' => round((float) ($ndcPricing['base_amount'] ?? 0), 2),
                'tax_amount' => round((float) ($ndcPricing['tax_amount'] ?? 0), 2),
                'total_amount' => round((float) ($ndcPricing['total_amount'] ?? 0), 2),
            ],
            'markup' => $totals,
            'payable_total' => $totals['display_amount'],
        ];

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'airline_code' => strtoupper((string) Arr::get($payload, 'primary_carrier', Arr::get($payload, 'owner'))),
            'primary_carrier' => strtoupper((string) Arr::get($payload, 'primary_carrier', Arr::get($payload, 'owner'))),
            'currency' => Arr::get($pricedOffer, 'currency', Arr::get($payload, 'currency', config('travelndc.currency', 'USD'))),
            'customer_email' => Auth::user()?->email,
            'customer_name' => Auth::user()?->name,
            'amount_base' => $pricing['ndc']['base_amount'],
            'amount_final' => $pricing['payable_total'],
            'status' => 'pending',
            'priced_offer_ref' => Arr::get($payload, 'offer_id'),
            'response_id' => Arr::get($payload, 'response_id'),
            'payment_reference' => null,
            'referral_code' => session('ref'),
            'passenger_summary' => [
                'segments' => count(Arr::get($payload, 'segments', [])),
                'offer_items' => count(Arr::get($payload, 'offer_items', [])),
            ],
            'itinerary_json' => json_encode([
                'segments' => Arr::get($payload, 'segments', []),
            ], JSON_UNESCAPED_SLASHES),
            'pricing_json' => json_encode($pricing, JSON_UNESCAPED_SLASHES),
        ]);

        Log::info('Booking created for priced offer', [
            'booking_id' => $booking->id,
            'offer_id' => Arr::get($payload, 'offer_id'),
        ]);

        return redirect()->back()->with([
            'pricedOffer' => [
                'offer_id' => Arr::get($payload, 'offer_id'),
                'owner' => Arr::get($payload, 'owner'),
                'response_id' => Arr::get($payload, 'response_id'),
                'currency' => Arr::get($pricedOffer, 'currency', Arr::get($payload, 'currency', config('travelndc.currency', 'USD'))),
                'segments' => Arr::get($payload, 'segments', []),
                'pricing' => $pricing,
            ],
            'bookingId' => $booking->id,
            'bookingCreated' => [
                'id' => $booking->id,
                'status' => $booking->status,
            ],
        ]);
    }
}
