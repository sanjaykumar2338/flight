<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AutoTicketTravelNdcOrder
{
    public function __construct(private readonly TravelNdcService $travelNdcService)
    {
    }

    public function handle(BookingPaid $event): void
    {
        $booking = $event->booking->fresh();

        if (!$booking || empty($booking->provider_order_id)) {
            return;
        }

        $providerData = $booking->provider_order_data;
        if (!is_array($providerData)) {
            $providerData = (array) $providerData;
        }

        $requiresNdc = Arr::get($providerData, 'demo_provider') !== 'videcom';

        if (!$requiresNdc) {
            return;
        }

        $existingTickets = Arr::get($providerData, 'tickets', []);

        if (!empty($existingTickets)) {
            return;
        }

        try {
            $result = $this->travelNdcService->ticketOrder(
                $booking->provider_order_id,
                strtoupper($booking->primary_carrier ?? $booking->airline_code ?? ''),
                (float) ($booking->amount_final ?? 0),
                $booking->currency ?? 'USD'
            );
        } catch (TravelNdcException $exception) {
            Log::warning('Automatic TravelNDC ticketing failed', [
                'booking_id' => $booking->id,
                'order_id' => $booking->provider_order_id,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $booking->update([
            'provider_order_data' => array_merge($providerData, [
                'tickets' => $result['tickets'] ?? [],
                'ticket_response' => $result,
            ]),
            'status' => $booking->status === 'paid' ? 'ticketed' : $booking->status,
            'paid_at' => $booking->paid_at ?? now(),
        ]);
    }
}
