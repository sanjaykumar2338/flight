<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferPriceRequest;
use App\Services\Pricing\PricingService;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OfferPricingController extends Controller
{
    public function __construct(
        private readonly TravelNdcService $travelNdcService,
        private readonly PricingService $pricingService
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
        $baseAmount = round((float) ($ndcPricing['base_amount'] ?? 0), 2);
        $taxAmount = round(
            (float) ($ndcPricing['tax_amount'] ?? (($ndcPricing['total_amount'] ?? 0) - $baseAmount)),
            2
        );
        $taxAmount = $taxAmount < 0 ? 0.0 : $taxAmount;

        $currency = Arr::get($pricedOffer, 'currency', Arr::get($payload, 'currency', config('travelndc.currency', 'USD')));
        $segments = Arr::get($payload, 'segments', []);
        $passengerSummary = $this->resolvePassengerSummary($payload);
        $context = $this->resolvePricingContext($payload, $segments, $carrier, $taxAmount, $currency);

        $pricingData = $this->pricingService->calculate(
            $segments,
            $passengerSummary,
            $baseAmount,
            $taxAmount,
            $currency,
            $context
        );

        $pricing = array_merge([
            'base_amount' => $baseAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => round((float) ($ndcPricing['total_amount'] ?? ($baseAmount + $taxAmount)), 2),
        ], $pricingData, [
            'currency' => $currency,
            'context' => $context,
            'passengers' => $passengerSummary,
        ]);

        $commissionAmount = round($pricing['payable_total'] - ($pricing['ndc']['base_amount'] + $pricing['ndc']['tax_amount']), 2);

        $itineraryPayload = [
            'segments' => Arr::get($payload, 'segments', []),
            'offer_items' => Arr::get($payload, 'offer_items', []),
        ];

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'airline_code' => strtoupper((string) Arr::get($payload, 'primary_carrier', Arr::get($payload, 'owner'))),
            'primary_carrier' => strtoupper((string) Arr::get($payload, 'primary_carrier', Arr::get($payload, 'owner'))),
            'currency' => Arr::get($pricedOffer, 'currency', Arr::get($payload, 'currency', config('travelndc.currency', 'USD'))),
            'customer_email' => Auth::user()?->email,
            'customer_name' => Auth::user()?->name,
            'amount_base' => $pricing['ndc']['base_amount'],
            'commission_amount' => $commissionAmount,
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
            'itinerary_json' => json_encode($itineraryPayload, JSON_UNESCAPED_SLASHES),
            'pricing_json' => json_encode($pricing, JSON_UNESCAPED_SLASHES),
        ]);

        Log::info('Booking created for priced offer', [
            'booking_id' => $booking->id,
            'offer_id' => Arr::get($payload, 'offer_id'),
        ]);

        $offerSnapshot = [
            'offer_id' => Arr::get($payload, 'offer_id'),
            'owner' => Arr::get($payload, 'owner'),
            'response_id' => Arr::get($payload, 'response_id'),
            'currency' => Arr::get($pricedOffer, 'currency', Arr::get($payload, 'currency', config('travelndc.currency', 'USD'))),
            'segments' => Arr::get($payload, 'segments', []),
            'offer_items' => Arr::get($payload, 'offer_items', []),
            'demo_provider' => Arr::get($payload, 'demo_provider'),
        ];

        $orderToken = base64_encode(json_encode($offerSnapshot, JSON_UNESCAPED_SLASHES));

        return redirect()->back()->with([
            'pricedOffer' => array_merge($offerSnapshot, [
                'pricing' => $pricing,
                'token' => $orderToken,
            ]),
            'bookingId' => $booking->id,
            'bookingCreated' => [
                'id' => $booking->id,
                'status' => $booking->status,
            ],
            'scrollTo' => 'payment-options',
        ]);
    }

    private function resolvePassengerSummary(array $payload): array
    {
        $summary = Arr::get($payload, 'pricing.passengers', Arr::get($payload, 'passenger_summary', []));
        $types = [];

        if (is_array($summary)) {
            foreach (Arr::get($summary, 'types', []) as $type => $count) {
                $types[strtoupper((string) $type)] = max(0, (int) $count);
            }
        }

        if (empty($types)) {
            $types = [
                'ADT' => max(0, (int) Arr::get($payload, 'passengers.ADT', 1)),
                'CHD' => max(0, (int) Arr::get($payload, 'passengers.CHD', 0)),
                'INF' => max(0, (int) Arr::get($payload, 'passengers.INF', 0)),
            ];
        }

        return [
            'types' => $types,
            'list' => array_keys(array_filter($types, fn ($count) => $count > 0)),
            'total' => array_sum($types),
        ];
    }

    private function resolvePricingContext(array $payload, array $segments, string $carrier, float $taxAmount, string $currency): array
    {
        $context = Arr::get($payload, 'pricing.context', Arr::get($payload, 'pricing_context', []));
        $context = is_array($context) ? $context : [];

        [$origin, $destination] = $this->extractEndpoints($segments, $context);

        $travelType = strtoupper((string) ($context['travel_type'] ?? ''));

        if (!in_array($travelType, ['OW', 'RT'], true)) {
            $travelType = $context['return_date'] ?? null ? 'RT' : 'OW';
        }

        $departureDate = $context['departure_date'] ?? $this->segmentDate($segments, 'departure', true);
        $returnDate = $context['return_date'] ?? $this->segmentDate($segments, 'arrival', false);

        return [
            'carrier' => strtoupper($context['carrier'] ?? $carrier),
            'origin' => strtoupper($origin ?? ''),
            'destination' => strtoupper($destination ?? ''),
            'travel_type' => $travelType,
            'cabin_class' => strtoupper((string) ($context['cabin_class'] ?? Arr::get($payload, 'cabin_class', 'ANY'))),
            'fare_type' => strtoupper((string) ($context['fare_type'] ?? 'ANY')),
            'promo_code' => $context['promo_code'] ?? Arr::get($payload, 'promo_code'),
            'sales_date' => $context['sales_date'] ?? Carbon::now()->toIso8601String(),
            'departure_date' => $this->toDateString($departureDate),
            'return_date' => $this->toDateString($returnDate),
            'rbd' => $context['rbd'] ?? $this->extractRbd($payload),
            'tax_amount' => $taxAmount,
            'currency' => $currency,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     * @param array<string, mixed> $context
     */
    private function extractEndpoints(array $segments, array $context): array
    {
        $first = is_array($segments) ? Arr::first($segments) : null;
        $last = is_array($segments) ? Arr::last($segments) : null;

        $origin = $first['origin'] ?? $context['origin'] ?? null;
        $destination = $last['destination'] ?? $context['destination'] ?? null;

        return [$origin, $destination];
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     */
    private function segmentDate(array $segments, string $key, bool $first = true): ?string
    {
        $segment = is_array($segments) ? ($first ? Arr::first($segments) : Arr::last($segments)) : null;

        return is_array($segment) ? ($segment[$key] ?? null) : null;
    }

    private function toDateString(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractRbd(array $payload): ?string
    {
        $contextRbd = Arr::get($payload, 'pricing.context.rbd', Arr::get($payload, 'pricing_context.rbd'));

        if (is_string($contextRbd) && $contextRbd !== '') {
            return strtoupper(substr($contextRbd, 0, 10));
        }

        $segments = Arr::get($payload, 'segments', []);

        if (is_array($segments) && !empty($segments)) {
            $candidate = $this->findRbdValue($segments[0]);

            if ($candidate) {
                return $candidate;
            }
        }

        $offerItems = Arr::get($payload, 'offer_items', []);

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
