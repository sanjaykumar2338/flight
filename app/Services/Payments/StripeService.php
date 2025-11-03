<?php

namespace App\Services\Payments;

use App\Models\Booking;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    private StripeClient $client;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * MODIFIED CONSTRUCTOR:
     * Removed the "?StripeClient $client = null" parameter.
     * This prevents Laravel from injecting an empty client and ensures
     * this class *always* constructs its own client with the API key.
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('stripe', []);

        $secret = trim((string) ($this->config['secret_key'] ?? ''));

        Log::debug('StripeService configuration loaded', [
            'mode' => $this->config['mode'] ?? null,
            'secret_present' => $secret !== '',
            'secret_prefix' => $secret !== '' ? substr($secret, 0, 8) : null,
            'public_present' => !empty($this->config['public_key']),
            'webhook_present' => !empty($this->config['webhook_secret']),
        ]);

        if ($secret === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        Log::debug('Jug', [
            'secret' => $secret
        ]);

        // Set API key for static calls (like Webhook)
        Stripe::setApiKey($secret);

        // MODIFIED: This line is no longer conditional.
        // It now *always* creates the client instance with the secret key.
        $this->client = new StripeClient([
            'api_key' => $secret,
        ]);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function createCheckoutSession(Booking $booking, string $email, array $metadata = []): Session
    {
        $successUrl = Arr::get($metadata, 'success_url', $this->config['success_url'] ?? '');
        $cancelUrl = Arr::get($metadata, 'cancel_url', $this->config['cancel_url'] ?? '');

        $currency = strtolower($booking->currency ?? ($metadata['currency'] ?? 'usd'));

        try {
            Log::debug('Stripe checkout session attempting', [
                'booking_id' => $booking->id,
                'mode' => $this->config['mode'] ?? null,
                'currency' => $currency,
                'email' => $email,
                'secret_length' => strlen((string) ($this->config['secret_key'] ?? '')),
                'secret_prefix' => substr((string) ($this->config['secret_key'] ?? ''), 0, 7),
            ]);
            return $this->client->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'customer_email' => $email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $this->amountToStripe($booking->amount_final ?? 0),
                        'product_data' => [
                            'name' => sprintf('Flight booking #%s', $booking->id),
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => array_merge($metadata, [
                    'booking_id' => $booking->id,
                ]),
                'success_url' => $this->appendSessionPlaceholder($successUrl),
                'cancel_url' => $cancelUrl ?: url()->route('bookings.show', $booking),
            ]);
        } catch (ApiErrorException $exception) {
            Log::error('Stripe checkout initialization failed', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
                'code' => $exception->getStripeCode(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function verifyWebhook(string $signature, string $payload): array
    {
        $secret = (string) ($this->config['webhook_secret'] ?? '');

        if ($secret === '') {
            throw new \RuntimeException('Stripe webhook secret is not configured.');
        }

        $event = Webhook::constructEvent($payload, $signature, $secret);

        return $event->jsonSerialize();
    }

    private function amountToStripe(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function appendSessionPlaceholder(string $url): string
    {
        if (str_contains($url, '{CHECKOUT_SESSION_ID}')) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'session_id={CHECKOUT_SESSION_ID}';
    }
}