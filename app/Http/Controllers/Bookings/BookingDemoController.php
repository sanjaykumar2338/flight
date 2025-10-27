<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Events\BookingPaid;
use App\Models\Booking;
use App\Models\ReferralStat;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingDemoController extends Controller
{
    public function show(Booking $booking)
    {
        $this->ensureDemoMode();

        $transaction = $booking->transactions()->latest()->first();

        return view('bookings.demo', [
            'booking' => $booking,
            'transaction' => $transaction,
        ]);
    }

    public function simulate(Request $request, Booking $booking, string $status): RedirectResponse
    {
        $this->ensureDemoMode();

        /** @var Transaction|null $transaction */
        $transaction = $booking->transactions()->latest()->first();

        if (!$transaction) {
            return redirect()->back()->withErrors([
                'simulation' => 'No transaction found to simulate.',
            ]);
        }

        $payload = $transaction->raw_payload ? json_decode($transaction->raw_payload, true) : [];
        $payload['demo_simulation'] = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];
        $transaction->raw_payload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $alreadyPaid = $booking->status === 'paid';

        if ($status === 'success') {
            $transaction->status = 'success';
            $booking->fill([
                'status' => 'paid',
                'payment_reference' => $transaction->reference,
                'paid_at' => $booking->paid_at ?? now(),
            ])->save();

            if (!$alreadyPaid && $booking->referral_code) {
                $stat = ReferralStat::firstOrCreate(['referral_code' => $booking->referral_code]);
                $stat->increment('bookings_count');
                $stat->increment('payments_count');
            }

            if (!$alreadyPaid) {
                event(new BookingPaid($booking, $transaction));
            }

            Log::info('Demo booking marked paid', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
            ]);

            $message = 'Booking marked as paid (demo).';
        } else {
            $transaction->status = 'failed';
            $booking->update(['status' => 'failed']);

            Log::info('Demo booking marked failed', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
            ]);

            $message = 'Booking flagged as failed (demo).';
        }

        $transaction->save();

        return redirect()->route('bookings.demo', $booking)->with('status', $message);
    }

    private function ensureDemoMode(): void
    {
        if (strcasecmp(config('paystack.mode', 'sandbox'), 'demo') !== 0) {
            abort(404);
        }
    }
}
