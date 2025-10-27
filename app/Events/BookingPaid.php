<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingPaid
{
    public bool $afterCommit = true;

    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?Transaction $transaction = null
    ) {
    }
}
