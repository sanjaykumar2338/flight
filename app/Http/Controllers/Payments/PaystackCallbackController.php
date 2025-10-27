<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

class PaystackCallbackController extends Controller
{
    public function __invoke(Booking $booking): RedirectResponse
    {
        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Payment confirmed. Booking is Paid.');
    }
}
