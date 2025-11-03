<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\StripeCheckoutRequest;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\Payments\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function checkout(StripeCheckoutRequest $request, Booking $booking, StripeService $stripe): JsonResponse|RedirectResponse
    {
        Stripe::setApiKey(config('stripe.secret_key'));

        if (!in_array($booking->status, ['pending', 'awaiting_payment', 'failed', 'payment_failed'], true)) {
            return $this->errorResponse($request, 'This booking is not available for payment.');
        }

        $booking->fill([
            'customer_email' => $request->input('email'),
            'customer_name' => $request->input('name'),
            'status' => 'awaiting_payment',
        ])->save();

        try {
            $session = $stripe->createCheckoutSession($booking, $booking->customer_email ?? $request->input('email'), [
                'success_url' => config('stripe.success_url'),
                'cancel_url' => config('stripe.cancel_url') ?: route('bookings.show', $booking),
            ]);
        } catch (ApiErrorException $exception) {
            Log::error('Stripe checkout session creation failed', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
                'code' => $exception->getStripeCode(),
            ]);

            $message = 'Unable to start Stripe checkout. Please try again later.';

            if (config('app.debug')) {
                $message .= ' ['.$exception->getMessage().']';
            }

            return $this->errorResponse($request, $message);
        }

        $booking->transactions()->create([
            'provider' => 'stripe',
            'mode' => config('stripe.mode', 'test'),
            'reference' => $session->id,
            'amount' => $booking->amount_final,
            'currency' => $booking->currency ?? 'USD',
            'status' => 'init',
            'raw_payload' => json_encode([
                'request' => [
                    'email' => $booking->customer_email,
                    'amount' => $booking->amount_final,
                    'currency' => $booking->currency,
                ],
                'response' => $session->toArray(),
            ], JSON_UNESCAPED_SLASHES),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => $session->url,
            ]);
        }

        return redirect()->away($session->url);
    }

    public function success(Request $request): View
    {
        $sessionId = (string) $request->query('session_id', '');

        return view('payments.stripe.success', [
            'sessionId' => $sessionId,
        ]);
    }

    private function errorResponse(Request $request, string $message, int $status = 422): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    'checkout' => [$message],
                ],
            ], $status);
        }

        return back()
            ->withErrors(['checkout' => $message])
            ->withInput($request->only(['email', 'name']));
    }
}
