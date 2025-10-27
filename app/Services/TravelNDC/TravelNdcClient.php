<?php

namespace App\Services\TravelNDC;

use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\PostmanCollectionLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TravelNdcClient
{
    private Client $client;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array<string, string>
     */
    private array $endpoints;

    private string $mode;

    public function __construct(?Client $client = null, ?array $config = null)
    {
        $this->config = $config ?? config('travelndc', []);
        $this->endpoints = $this->config['endpoints'] ?? [];
        $this->mode = (string) ($this->config['mode'] ?? 'sandbox');

        $clientOptions = [
            'timeout' => $this->config['timeout'] ?? 30,
            'verify' => $this->config['verify_ssl'] ?? false,
        ];

        if (!empty($this->config['base_url'])) {
            $clientOptions['base_uri'] = rtrim($this->config['base_url'], '/') . '/';
        }

        $this->client = $client ?? new Client($clientOptions);
    }

    /**
     * @param array<string, string> $headers
     */
    public function post(string $endpointKey, string $body, array $headers = []): string
    {
        if ($this->isDemoMode()) {
            return $this->demoResponse($endpointKey);
        }

        $defaultHeaders = [
            'Content-Type' => 'application/xml;charset=UTF-8',
            'Accept' => 'application/xml',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        $path = $this->resolveEndpoint($endpointKey);

        $options = [
            'headers' => $headers,
            'body' => $body,
        ];

        if ($credentials = $this->clientCredentials()) {
            $options['auth'] = $credentials;
        }

        if (empty($this->config['base_url']) && !str_starts_with($path, 'http')) {
            throw new TravelNdcException('TravelNDC base URL is not configured.');
        }

        $requestPath = $path;

        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            $requestPath = ltrim($path, '/');
        }

        try {
            $response = $this->client->post($requestPath, [
                ...$options,
            ]);
        } catch (GuzzleException $exception) {
            throw new TravelNdcException("TravelNDC request failed: {$exception->getMessage()}", previous: $exception);
        }

        return (string) $response->getBody();
    }

    private function isDemoMode(): bool
    {
        return strcasecmp($this->mode, 'demo') === 0;
    }

    private function demoResponse(string $endpointKey): string
    {
        $responses = $this->config['demo_responses'] ?? [];
        $response = is_array($responses) ? ($responses[$endpointKey] ?? null) : null;

        if (!is_string($response) || trim($response) === '') {
            $response = PostmanCollectionLoader::instance()->response($endpointKey);
        }

        if (!is_string($response) || trim($response) === '') {
            throw new TravelNdcException("No demo response available for {$endpointKey} endpoint.");
        }

        return trim($response);
    }

    private function clientCredentials(): array
    {
        if (!empty($this->config['client_id']) && !empty($this->config['client_secret'])) {
            return [$this->config['client_id'], $this->config['client_secret']];
        }

        return [];
    }

    private function resolveEndpoint(string $endpointKey): string
    {
        if (str_starts_with($endpointKey, 'http://') || str_starts_with($endpointKey, 'https://')) {
            return $endpointKey;
        }

        $path = $this->endpoints[$endpointKey] ?? null;

        if ($path === null) {
            throw new TravelNdcException("Unknown TravelNDC endpoint key [{$endpointKey}].");
        }

        return $path;
    }
}
