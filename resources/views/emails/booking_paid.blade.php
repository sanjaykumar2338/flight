<p>Hello {{ $booking->customer_name ?? 'traveler' }},</p>

<p>We have confirmed your payment for booking <strong>#{{ $booking->id }}</strong>.</p>

<p>
    Amount paid: <strong>{{ $booking->currency }} {{ number_format((float) ($booking->amount_final ?? 0), 2) }}</strong><br>
    Reference: <strong>{{ $booking->payment_reference ?? 'N/A' }}</strong>
</p>

<p>Thank you for booking with us.</p>
