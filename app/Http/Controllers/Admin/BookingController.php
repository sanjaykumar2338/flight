<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::query()
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');

                if ($status === 'failed') {
                    $query->whereIn('status', ['failed', 'payment_failed']);
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($request->filled('airline'), fn ($query) => $query->where('airline_code', strtoupper($request->input('airline'))))
            ->when($request->filled('ref'), fn ($query) => $query->where('referral_code', $request->input('ref')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $request->only(['status', 'airline', 'ref', 'date_from', 'date_to']),
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['transactions' => fn ($query) => $query->latest()]);

        $transactions = $booking->transactions;
        $firstTransaction = $transactions->last();
        $latestTransaction = $transactions->first();

        $timeline = collect([
            [
                'label' => 'Booking Created',
                'description' => 'Initial booking captured from priced offer.',
                'timestamp' => $booking->created_at,
            ],
            $firstTransaction ? [
                'label' => 'Checkout Initialized',
                'description' => 'Paystack checkout started with reference ' . $firstTransaction->reference,
                'timestamp' => $firstTransaction->created_at,
            ] : null,
            ($latestTransaction && $latestTransaction->status !== 'init') ? [
                'label' => 'Webhook Processed',
                'description' => 'Paystack webhook received (' . ucfirst($latestTransaction->status) . ').',
                'timestamp' => $latestTransaction->updated_at,
            ] : null,
            ($booking->paid_at) ? [
                'label' => 'Marked Paid',
                'description' => 'Booking marked as paid.',
                'timestamp' => $booking->paid_at,
            ] : (($booking->status === 'failed' || $booking->status === 'payment_failed') ? [
                'label' => 'Payment Failed',
                'description' => 'Booking marked as failed.',
                'timestamp' => $latestTransaction?->updated_at ?? $booking->updated_at,
            ] : null),
        ])
            ->filter(fn ($event) => $event && $event['timestamp'])
            ->sortBy('timestamp')
            ->values()
            ->all();

        return view('admin.bookings.show', [
            'booking' => $booking,
            'transactions' => $transactions,
            'timeline' => $timeline,
            'latestTransaction' => $latestTransaction,
            'isDemo' => strcasecmp(config('paystack.mode', 'sandbox'), 'demo') === 0,
        ]);
    }
}
