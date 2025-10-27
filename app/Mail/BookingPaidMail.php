<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingPaidMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Booking #' . $this->booking->id . ' payment confirmed')
            ->view('emails.booking_paid')
            ->with([
                'booking' => $this->booking,
            ]);
    }
}
