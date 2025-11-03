<?php

namespace App\Http\Controllers\Webhooks;

use App\Events\BookingPaid;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ReferralStat;
use App\Models\Transaction;
use App\Services\Payments\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeService $stripe): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $stripe->verifyWebhook($signature, $payload);
        } catch (SignatureVerificationException $exception) {
            Log::warning('Stripe webhook signature verification failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['status' => 'ignored'], 400);
        } catch (\Throwable $throwable) {
            Log::error('Stripe webhook processing error', [
                'message' => $throwable->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }

        $type = $event['type'] ?? null;
        $session = $event['data']['object'] ?? [];
        $reference = $session['id'] ?? null;

        if (!$reference) {
            Log::warning('Stripe webhook missing session reference', ['event' => $event]);

            return response()->json(['status' => 'ignored']);
        }

        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()
            ->where('reference', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('Stripe webhook for unknown transaction', ['reference' => $reference]);

            return response()->json(['status' => 'ok']);
        }

        $raw = $transaction->raw_payload ? json_decode($transaction->raw_payload, true) : [];
        $raw['webhook'][] = $event;
        $transaction->raw_payload = json_encode($raw, JSON_UNESCAPED_SLASHES);

        DB::transaction(function () use ($transaction, $type, $session) {
            $transaction->status = $this->statusFromEvent($type, $session['payment_status'] ?? null);
            $transaction->save();

            /** @var Booking|null $booking */
            $booking = $transaction->booking()->lockForUpdate()->first();

            if (!$booking) {
                return;
            }

            $alreadyPaid = $booking->status === 'paid';

            if ($transaction->status === 'success') {
                $booking->fill([
                    'status' => 'paid',
                    'paid_at' => $booking->paid_at ?? now(),
                    'payment_reference' => $transaction->reference,
                ])->save();

                if (!$alreadyPaid && $booking->referral_code) {
                    $stat = ReferralStat::query()->firstOrCreate(
                        ['referral_code' => $booking->referral_code]
                    );

                    $stat->increment('bookings_count');
                    $stat->increment('payments_count');
                }

                if (!$alreadyPaid) {
                    event(new BookingPaid($booking, $transaction));
                }
            } elseif ($transaction->status === 'failed') {
                $booking->fill([
                    'status' => 'failed',
                ])->save();
            }
        });

        return response()->json(['status' => 'ok']);
    }

    private function statusFromEvent(?string $type, ?string $paymentStatus): string
    {
        $type = strtolower((string) $type);
        $paymentStatus = strtolower((string) $paymentStatus);

        if ($type === 'checkout.session.completed' || $paymentStatus === 'paid') {
            return 'success';
        }

        if (in_array($type, ['checkout.session.expired', 'payment_intent.payment_failed'], true) || $paymentStatus === 'unpaid') {
            return 'failed';
        }

        return 'pending';
    }
}
