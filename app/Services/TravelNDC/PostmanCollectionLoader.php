<?php

namespace App\Services\TravelNDC;

use Illuminate\Support\Arr;
use RuntimeException;

class PostmanCollectionLoader
{
    private const COLLECTION_FILENAME = 'TravelNDC test API sample.postman_collection.json';

    /**
     * @var array<string, array<int, string>>
     */
    private const TARGET_PATTERNS = [
        'airshopping' => ['airshopping', 'airshoping'],
        'offerprice' => ['offerprice'],
        'ordercreate' => ['ordercreate'],
        'orderretrieve' => ['orderretrieve'],
        'ordercancel' => ['ordercancel'],
    ];

    private static ?self $instance = null;

    private ?string $baseUrl = null;

    /**
     * @var array<string, string>
     */
    private array $endpoints = [];

    /**
     * @var array<string, string>
     */
    private array $templates = [];

    /**
     * @var array<string, string>
     */
    private array $responses = [];

    private function __construct(private readonly string $path)
    {
        $this->load();
    }

    public static function instance(?string $path = null): self
    {
        if (self::$instance === null) {
            $resolvedPath = $path ?? base_path(self::COLLECTION_FILENAME);
            self::$instance = new self($resolvedPath);
        }

        return self::$instance;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function endpointPath(string $key): ?string
    {
        return $this->endpoints[$key] ?? null;
    }

    public function template(string $key): ?string
    {
        return $this->templates[$key] ?? null;
    }

    public function response(string $key): ?string
    {
        return $this->responses[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function responses(): array
    {
        return $this->responses;
    }

    /**
     * @return array{base_url: ?string, endpoints: array<string, string>, templates: array<string, string>, responses: array<string, string>}
     */
    public function snapshot(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'endpoints' => $this->endpoints,
            'templates' => $this->templates,
            'responses' => $this->responses,
        ];
    }

    private function load(): void
    {
        if (!is_file($this->path)) {
            throw new RuntimeException(sprintf('TravelNDC Postman collection not found at %s', $this->path));
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read TravelNDC Postman collection at %s', $this->path));
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('TravelNDC Postman collection contains invalid JSON.');
        }

        $items = Arr::get($decoded, 'item');

        if (!is_array($items)) {
            throw new RuntimeException('TravelNDC Postman collection does not contain any items.');
        }

        $this->traverseItems($items);

        foreach (self::TARGET_PATTERNS as $key => $_) {
            if (!isset($this->endpoints[$key])) {
                throw new RuntimeException("TravelNDC Postman collection missing endpoint definition for {$key}.");
            }
        }

        foreach (['airshopping', 'offerprice'] as $templateKey) {
            if (!isset($this->templates[$templateKey])) {
                throw new RuntimeException("TravelNDC Postman collection missing XML template for {$templateKey}.");
            }
        }
    }

    private function traverseItems(array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['item']) && is_array($item['item'])) {
                $this->traverseItems($item['item']);
                continue;
            }

            if (!isset($item['name'], $item['request']) || !is_array($item['request'])) {
                continue;
            }

            $targetKey = $this->matchTargetKey((string) $item['name']);

            if ($targetKey === null) {
                continue;
            }

            $request = $item['request'];

            $body = Arr::get($request, 'body.raw');

            if (is_string($body)) {
                $this->templates[$targetKey] = $this->normalizeXml($body);
            }

            $rawUrl = $this->extractRawUrl($request);

            if ($rawUrl !== null) {
                [$baseUrl, $path] = $this->splitUrl($rawUrl);

                if ($baseUrl !== null && $this->baseUrl === null) {
                    $this->baseUrl = $baseUrl;
                }

                if ($path !== null) {
                    $this->endpoints[$targetKey] = $path;
                }
            }

            if (!isset($this->responses[$targetKey]) && isset($item['response']) && is_array($item['response'])) {
                foreach ($item['response'] as $response) {
                    $body = Arr::get($response, 'body');

                    if (is_string($body) && trim($body) !== '') {
                        $this->responses[$targetKey] = $this->normalizeXml($body);
                        break;
                    }
                }
            }
        }
    }

    private function matchTargetKey(string $name): ?string
    {
        $normalized = strtolower(preg_replace('/[^a-z]/i', '', $name) ?? '');

        foreach (self::TARGET_PATTERNS as $key => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function extractRawUrl(array $request): ?string
    {
        $url = Arr::get($request, 'url');

        if (is_string($url) && $url !== '') {
            return $url;
        }

        if (is_array($url) && isset($url['raw']) && is_string($url['raw'])) {
            return $url['raw'];
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitUrl(string $rawUrl): array
    {
        $rawUrl = trim($rawUrl);

        if ($rawUrl === '') {
            return [null, null];
        }

        if (!preg_match('#^https?://#i', $rawUrl)) {
            $rawUrl = 'https://' . ltrim($rawUrl, '/');
        }

        $parts = parse_url($rawUrl);

        if ($parts === false || !isset($parts['host'])) {
            return [null, null];
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $path = $parts['path'] ?? '';

        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== ''));

        $endpoint = array_pop($segments);
        $basePath = implode('/', $segments);

        $baseUrl = sprintf('%s://%s', $scheme, $host);

        if ($basePath !== '') {
            $baseUrl .= '/' . $basePath;
        }

        $normalizedPath = $endpoint ? '/' . ltrim($endpoint, '/') : null;

        return [$baseUrl, $normalizedPath];
    }

    private function normalizeXml(string $xml): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $xml);

        return trim($normalized);
    }
}
