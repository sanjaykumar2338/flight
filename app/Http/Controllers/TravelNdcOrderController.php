<?php

namespace App\Http\Controllers;

use App\Http\Requests\TravelNdcOrderRequest;
use App\Models\Booking;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class TravelNdcOrderController extends Controller
{
    public function __construct(private readonly TravelNdcService $travelNdcService)
    {
    }

    public function __invoke(TravelNdcOrderRequest $request)
    {
        $offerPayload = $request->decodedOffer();

        if (Arr::get($offerPayload, 'demo_provider') === 'videcom') {
            return $this->errorResponse($request, 'TravelNDC booking is not available for this offer.');
        }

        $booking = Booking::findOrFail($request->integer('booking_id'));

        if ($booking->provider_order_id) {
            return $this->errorResponse($request, 'This booking already has a TravelNDC order.');
        }

        $passenger = [
            'ptc' => $request->input('ptc'),
            'birthdate' => $request->input('birthdate'),
            'gender' => $request->input('gender'),
            'title' => $request->input('title'),
            'given_name' => $request->input('given_name'),
            'surname' => $request->input('surname'),
        ];

        $contact = [
            'email' => $request->input('contact_email'),
            'phone' => $request->input('contact_phone'),
        ];

        try {
            $order = $this->travelNdcService->createOrder($offerPayload, [$passenger], $contact);
        } catch (TravelNdcException $exception) {
            $message = $exception->getMessage();

            if (stripos($message, 'responseid') !== false && stripos($message, 'expired') !== false) {
                $message = 'This offer has expired. Please re-price or re-select the itinerary from search results to get a fresh response, then try again.';
            }

            return $this->errorResponse($request, $message, 422, true);
        }

        $booking->update([
            'provider_order_id' => $order['order_id'] ?? null,
            'provider_order_data' => array_merge($booking->provider_order_data ?? [], [
                'order_id' => $order['order_id'] ?? null,
                'response_id' => $order['response_id'] ?? null,
                'owner' => Arr::get($offerPayload, 'owner'),
            ]),
        ]);

        session()->flash('travelNdcOrder', [
            'order_id' => $order['order_id'] ?? null,
            'response_id' => $order['response_id'] ?? null,
        ]);
        session()->flash('scrollTo', 'payment-options');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'order_id' => $order['order_id'] ?? null,
                'booking_id' => $booking->id,
                'message' => 'TravelNDC order created successfully.',
            ]);
        }

        return redirect()->back();
    }

    private function errorResponse(TravelNdcOrderRequest $request, string $message, int $status = 422, bool $withInput = false)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        $redirect = redirect()->back()->withErrors([
            'ndc_order' => $message,
        ]);

        return $withInput ? $redirect->withInput() : $redirect;
    }
}
