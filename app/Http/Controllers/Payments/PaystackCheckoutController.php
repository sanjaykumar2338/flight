<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaystackCheckoutRequest;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\Payments\Exceptions\PaystackException;
use App\Services\Payments\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PaystackCheckoutController extends Controller
{
    public function __invoke(PaystackCheckoutRequest $request, PaystackService $paystack): RedirectResponse
    {
        /** @var Booking $booking */
        $booking = Booking::query()->findOrFail($request->integer('booking_id'));

        if (!in_array($booking->status, ['pending', 'awaiting_payment', 'failed', 'payment_failed'], true)) {
            return back()->withErrors([
                'checkout' => 'This booking is not available for payment.',
            ]);
        }

        $booking->fill([
            'customer_email' => $request->input('email'),
            'customer_name' => $request->input('name'),
            'status' => 'awaiting_payment',
        ])->save();

        $reference = 'PSK-' . Str::upper(Str::random(16));
        $mode = config('paystack.mode', 'sandbox');

        $metadata = [
            'reference' => $reference,
            'booking_id' => $booking->id,
            'callback_url' => URL::route('bookings.paystack.callback', ['booking' => $booking->id]),
        ];

        try {
            $response = $paystack->initializeCheckout($booking, $booking->customer_email ?? $request->input('email'), $metadata);
        } catch (PaystackException $exception) {
            Log::error('Paystack checkout initialization failed', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'checkout' => 'Unable to start Paystack checkout. Please try again later.',
            ]);
        }

        /** @var Transaction $transaction */
        $transaction = $booking->transactions()->create([
            'provider' => 'paystack',
            'mode' => $mode,
            'reference' => $response['reference'],
            'amount' => $booking->amount_final,
            'currency' => $booking->currency ?? config('paystack.currency', 'NGN'),
            'status' => 'init',
            'raw_payload' => json_encode([
                'mode' => $mode,
                'request' => [
                    'email' => $booking->customer_email,
                    'amount' => $booking->amount_final,
                    'currency' => $booking->currency,
                    'reference' => $response['reference'],
                ],
                'response' => $response,
            ], JSON_UNESCAPED_SLASHES),
        ]);

        Log::info('Paystack transaction initialized', [
            'booking_id' => $booking->id,
            'transaction_id' => $transaction->id,
            'reference' => $transaction->reference,
        ]);

        $booking->update([
            'payment_reference' => $response['reference'],
        ]);

        return redirect()->away($response['authorization_url']);
    }
}
