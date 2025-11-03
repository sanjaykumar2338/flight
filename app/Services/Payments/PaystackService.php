<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Services\Payments\Exceptions\PaystackException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class PaystackService
{
    private Client $client;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    private string $mode;

    private LoggerInterface $logger;

    public function __construct(?Client $client = null, ?LoggerInterface $logger = null, ?array $config = null)
    {
        $this->config = $config ?? config('paystack', []);
        $this->mode = (string) ($this->config['mode'] ?? 'sandbox');

        $baseUri = rtrim((string) ($this->config['payment_url'] ?? 'https://api.paystack.co'), '/') . '/';

        if ($client && $client->getConfig('base_uri')) {
            $this->client = $client;
        } else {
            $this->client = new Client([
                'base_uri' => $baseUri,
                'timeout' => 30,
            ]);
        }

        $this->logger = $logger ?? LogFacade::channel(config('logging.default'));
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{authorization_url: string, reference: string, access_code?: string}
     */
    public function initializeCheckout(Booking $booking, string $email, array $metadata = []): array
    {
        $reference = $metadata['reference'] ?? ('PSK-' . Str::uuid()->toString());

        if ($this->isDemoMode()) {
            return $this->fakeInitialization($booking, $reference);
        }

        $currency = strtoupper((string) ($this->config['currency'] ?? 'NGN'));

        $payload = [
            'email' => $email,
            'amount' => $this->formatAmountForPaystack($booking->amount_final ?: 0),
            'currency' => $currency,
            'reference' => $reference,
            'metadata' => array_merge($metadata, [
                'booking_id' => $booking->id,
                'referral_code' => $booking->referral_code,
                'mode' => $this->mode,
            ]),
        ];

        $this->logger->info('Paystack checkout payload prepared', [
            'booking_id' => $booking->id,
            'reference' => $reference,
            'currency' => $currency,
            'amount_minor' => $payload['amount'],
            'mode' => $this->mode,
        ]);

        if (!empty($metadata['callback_url'])) {
            $payload['callback_url'] = $metadata['callback_url'];
        }

        try {
            $response = $this->client->post('transaction/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey(),
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $exception) {
            throw new PaystackException('Unable to initialize Paystack transaction: ' . $exception->getMessage(), previous: $exception);
        }

        $body = (string) $response->getBody();

        $decoded = json_decode($body, true);

        if (!is_array($decoded) || !Arr::get($decoded, 'status')) {
            $message = Arr::get($decoded, 'message', 'Unknown Paystack error');
            $this->logger->error('Paystack initialization failed', [
                'booking_id' => $booking->id,
                'response' => $decoded,
            ]);

            throw new PaystackException('Paystack initialization failed: ' . $message);
        }

        /** @var array{authorization_url?: string, reference?: string, access_code?: string} $data */
        $data = Arr::get($decoded, 'data', []);

        $authUrl = $data['authorization_url'] ?? null;
        $reference = $data['reference'] ?? $reference;

        if (!$authUrl || !$reference) {
            throw new PaystackException('Invalid Paystack response when initializing transaction.');
        }

        return [
            'authorization_url' => $authUrl,
            'reference' => $reference,
            'access_code' => $data['access_code'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new PaystackException('Paystack reference is required for verification.');
        }

        if ($this->isDemoMode()) {
            return [
                'reference' => $reference,
                'status' => 'success',
            ];
        }

        try {
            $response = $this->client->get('transaction/verify/' . urlencode($reference), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey(),
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new PaystackException('Unable to verify Paystack transaction: ' . $exception->getMessage(), previous: $exception);
        }

        $decoded = json_decode((string) $response->getBody(), true);

        if (!is_array($decoded) || !Arr::get($decoded, 'status')) {
            $message = Arr::get($decoded, 'message', 'Unknown Paystack verification error');

            $this->logger->warning('Paystack verification failed', [
                'reference' => $reference,
                'response' => $decoded,
            ]);

            throw new PaystackException('Paystack verification failed: ' . $message);
        }

        return Arr::get($decoded, 'data', []);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function verifyWebhook(string $signature, array $payload): array
    {
        if ($this->isDemoMode()) {
            return $payload;
        }

        if ($signature === '' || !$this->secretKey()) {
            throw new PaystackException('Missing Paystack webhook signature.');
        }

        $computed = hash_hmac('sha512', json_encode($payload, JSON_THROW_ON_ERROR), $this->secretKey());

        if (!hash_equals($computed, $signature)) {
            throw new PaystackException('Invalid Paystack webhook signature.');
        }

        return $payload;
    }

    private function isDemoMode(): bool
    {
        return strcasecmp($this->mode, 'demo') === 0;
    }

    private function secretKey(): string
    {
        return (string) ($this->config['secret_key'] ?? '');
    }

    private function formatAmountForPaystack(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * @return array{authorization_url: string, reference: string}
     */
    private function fakeInitialization(Booking $booking, string $reference): array
    {
        $url = route('bookings.demo', ['booking' => $booking->id, 'reference' => $reference]);

        return [
            'authorization_url' => $url,
            'reference' => $reference,
        ];
    }
}
