<?php

namespace App\Http\Controllers\Payments;

use App\Events\BookingPaid;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ReferralStat;
use App\Models\Transaction;
use App\Services\Payments\Exceptions\PaystackException;
use App\Services\Payments\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '[]', true) ?? [];
        $signature = (string) $request->header('x-paystack-signature', '');

        try {
            $data = $paystack->verifyWebhook($signature, $payload);
        } catch (PaystackException $exception) {
            Log::warning('Paystack webhook signature verification failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['status' => 'ignored'], 400);
        }

        $reference = Arr::get($data, 'data.reference');

        if (!$reference) {
            Log::warning('Paystack webhook missing reference', ['payload' => $data]);

            return response()->json(['status' => 'ignored']);
        }

        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()->where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning('Paystack webhook for unknown transaction', [
                'reference' => $reference,
            ]);

            return response()->json(['status' => 'ok']);
        }

        $status = Arr::get($data, 'data.status', Arr::get($data, 'status'));
        $event = Arr::get($data, 'event');

        $raw = $transaction->raw_payload ? json_decode($transaction->raw_payload, true) : [];
        $raw['webhook'] = $data;
        $transaction->raw_payload = json_encode($raw, JSON_UNESCAPED_SLASHES);

        DB::transaction(function () use ($transaction, $status, $event, $reference, $data) {
            $transaction->status = $this->statusFromWebhook($status, $event);
            $transaction->save();

            /** @var Booking $booking */
            $booking = $transaction->booking()->lockForUpdate()->first();

            if (!$booking) {
                return;
            }

            $alreadyPaid = $booking->status === 'paid';

            if ($transaction->status === 'success') {
                $booking->fill([
                    'status' => 'paid',
                    'payment_reference' => $reference,
                    'paid_at' => $booking->paid_at ?? now(),
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

    private function statusFromWebhook(?string $status, ?string $event): string
    {
        $status = strtolower((string) $status);
        $event = strtolower((string) $event);

        if ($event === 'charge.success' || $status === 'success') {
            return 'success';
        }

        if (in_array($status, ['failed', 'abandoned'], true)) {
            return 'failed';
        }

        return 'pending';
    }
}
