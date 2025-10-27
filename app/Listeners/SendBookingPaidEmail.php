<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Mail\BookingPaidMail;
use Illuminate\Support\Facades\Mail;

class SendBookingPaidEmail
{
    public function handle(BookingPaid $event): void
    {
        $email = $event->booking->customer_email;

        if (!$email) {
            return;
        }

        Mail::to($email)->send(new BookingPaidMail($event->booking));
    }
}
