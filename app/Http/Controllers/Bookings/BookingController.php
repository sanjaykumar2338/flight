<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        if (!$userId) {
            abort(404);
        }

        $bookings = Booking::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate(15);

        return view('bookings.index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Booking $booking): View
    {
        $this->ensureOwnsBooking($booking);

        $booking->load(['transactions' => fn ($query) => $query->latest()]);

        return view('bookings.show', [
            'booking' => $booking,
            'transactions' => $booking->transactions,
            'latestTransaction' => $booking->transactions->first(),
            'isDemo' => strcasecmp(config('paystack.mode', 'sandbox'), 'demo') === 0,
        ]);
    }

    private function ensureOwnsBooking(Booking $booking): void
    {
        if ($booking->user_id !== Auth::id()) {
            abort(404);
        }
    }
}
