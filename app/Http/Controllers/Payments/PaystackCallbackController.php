<?php

namespace App\Http\Controllers\Payments;

use App\Events\BookingPaid;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\Payments\Exceptions\PaystackException;
use App\Services\Payments\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackCallbackController extends Controller
{
    public function __invoke(Request $request, Booking $booking, PaystackService $paystack): RedirectResponse
    {
        $reference = (string) $request->query('reference', $booking->payment_reference ?? '');

        if ($reference === '') {
            Log::warning('Paystack callback missing reference', ['booking_id' => $booking->id]);

            return $this->redirectWithStatus($booking, 'We received a payment callback but no reference was supplied. Please contact support if the payment does not update shortly.');
        }

        try {
            $verification = $paystack->verifyTransaction($reference);
        } catch (PaystackException $exception) {
            Log::warning('Paystack callback verification failed', [
                'booking_id' => $booking->id,
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            return $this->redirectWithStatus($booking, 'We are unable to confirm the Paystack payment at the moment. Our team will review it shortly.');
        }

        $normalizedStatus = $this->normalizeStatus($verification['status'] ?? null);

        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()
            ->where('reference', $reference)
            ->where('booking_id', $booking->id)
            ->first();

        DB::transaction(function () use ($transaction, $verification, $normalizedStatus, $booking, $reference) {
            if ($transaction) {
                $raw = $transaction->raw_payload ? json_decode($transaction->raw_payload, true) : [];
                $raw['verification'] = $verification;

                $transaction->status = $normalizedStatus;
                $transaction->raw_payload = json_encode($raw, JSON_UNESCAPED_SLASHES);
                $transaction->save();
            }

            $booking->refresh();
            $alreadyPaid = $booking->status === 'paid';

            if ($normalizedStatus === 'success') {
                $booking->fill([
                    'status' => 'paid',
                    'payment_reference' => $reference,
                    'paid_at' => $booking->paid_at ?? now(),
                ])->save();

                if (!$alreadyPaid) {
                    event(new BookingPaid($booking, $transaction));
                }
            } elseif ($normalizedStatus === 'failed') {
                $booking->fill(['status' => 'failed'])->save();
            }
        });

        $message = match ($normalizedStatus) {
            'success' => 'Payment confirmed. Booking is Paid.',
            'failed' => 'Paystack reported this payment as failed.',
            default => 'Payment verification is pending. We will update the booking once Paystack confirms the status.',
        };

        return $this->redirectWithStatus($booking, $message);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'success' => 'success',
            'failed', 'abandoned' => 'failed',
            default => 'pending',
        };
    }

    private function redirectWithStatus(Booking $booking, string $message): RedirectResponse
    {
        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $message);
    }
}
