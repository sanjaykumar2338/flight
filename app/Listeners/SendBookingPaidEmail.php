<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Mail\BookingPaidMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingPaidEmail
{
    public function handle(BookingPaid $event): void
    {
        $email = $event->booking->customer_email;

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new BookingPaidMail($event->booking));
        } catch (\Throwable $throwable) {
            Log::warning('Failed to send booking paid email', [
                'booking_id' => $event->booking->id,
                'email' => $email,
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}
