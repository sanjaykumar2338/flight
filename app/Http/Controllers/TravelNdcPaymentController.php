<?php

namespace App\Http\Controllers;

use App\Http\Requests\TravelNdcPaymentRequest;
use App\Models\Booking;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class TravelNdcPaymentController extends Controller
{
    public function __construct(private readonly TravelNdcService $travelNdcService)
    {
    }

    public function __invoke(TravelNdcPaymentRequest $request): RedirectResponse
    {
        $booking = Booking::findOrFail($request->integer('booking_id'));

        if (!$booking->provider_order_id) {
            return redirect()->back()->withErrors([
                'ndc_payment' => 'Create a TravelNDC order before processing payment.',
            ]);
        }

        $data = $booking->provider_order_data ?? [];
        $owner = Arr::get($data, 'owner', $booking->primary_carrier);

        if (!$owner) {
            return redirect()->back()->withErrors([
                'ndc_payment' => 'Unable to determine owning airline for this order.',
            ]);
        }

        try {
            $result = $this->travelNdcService->ticketOrder(
                $booking->provider_order_id,
                strtoupper($owner),
                (float) ($booking->amount_final ?? 0),
                $booking->currency ?? 'USD'
            );
        } catch (TravelNdcException $exception) {
            return redirect()->back()->withErrors([
                'ndc_payment' => $exception->getMessage(),
            ]);
        }

        $booking->update([
            'provider_order_data' => array_merge($data, [
                'tickets' => $result['tickets'] ?? [],
                'ticket_response' => $result,
            ]),
            'status' => $booking->status === 'pending' ? 'ticketed' : $booking->status,
            'paid_at' => $booking->paid_at ?? Carbon::now(),
        ]);

        session()->flash('travelNdcTickets', $result['tickets'] ?? []);
        session()->flash('scrollTo', 'payment-options');

        return redirect()->back();
    }
}
