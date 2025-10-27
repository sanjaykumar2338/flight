<?php

use App\Services\TravelNDC\PostmanCollectionLoader;
use Illuminate\Support\Str;

$loader = PostmanCollectionLoader::instance();

$agencyId = env('TRAVELNDC_AGENCY_ID') ?: null;
$mode = env('TRAVELNDC_MODE', 'sandbox');

$normalizeUrl = static function (?string $value, ?string $agencyId = null): ?string {
    if ($value === null || $value === '') {
        return null;
    }

    $url = trim($value);

    if ($agencyId !== null) {
        $url = str_replace('xxxxxxxx', $agencyId, $url);
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    return rtrim($url, '/');
};

$baseUrl = $normalizeUrl(env('TRAVELNDC_BASE_URL') ?: $loader->baseUrl(), $agencyId);

if ($baseUrl === null && $mode !== 'demo') {
    throw new RuntimeException('TravelNDC base URL is not configured. Set TRAVELNDC_BASE_URL or ensure the Postman collection defines it.');
}

$resolveEndpoint = static function (string $key, string $envKey) use ($loader, $agencyId): string {
    $envValue = env($envKey);
    $candidate = is_string($envValue) && $envValue !== '' ? $envValue : $loader->endpointPath($key);

    if ($candidate === null) {
        throw new RuntimeException("TravelNDC endpoint path for {$key} is not configured.");
    }

    $candidate = trim($candidate);

    if ($agencyId !== null) {
        $candidate = str_replace('xxxxxxxx', $agencyId, $candidate);
    }

    if (Str::startsWith($candidate, 'http://') || Str::startsWith($candidate, 'https://')) {
        $parts = parse_url($candidate);
        $candidate = $parts['path'] ?? '/';
    }

    return '/' . ltrim($candidate, '/');
};

$templates = [
    'airshopping' => $loader->template('airshopping'),
    'offerprice' => $loader->template('offerprice'),
];

foreach ($templates as $key => $template) {
    if ($template === null || $template === '') {
        throw new RuntimeException("TravelNDC XML template for {$key} is missing.");
    }
}

return [
    'mode' => $mode,
    'base_url' => $baseUrl,
    'client_id' => env('TRAVELNDC_CLIENT_ID'),
    'client_secret' => env('TRAVELNDC_CLIENT_SECRET'),
    'agency_id' => $agencyId,
    'agency_name' => env('TRAVELNDC_AGENCY_NAME'),
    'agent_user_id' => env('TRAVELNDC_AGENT_USER_ID'),
    'target_branch' => env('TRAVELNDC_TARGET_BRANCH'),
    'currency' => env('TRAVELNDC_CURRENCY', 'USD'),
    'timeout' => (int) env('TRAVELNDC_HTTP_TIMEOUT', 30),
    'verify_ssl' => (bool) env('TRAVELNDC_VERIFY_SSL', false),
    'endpoints' => [
        'airshopping' => $resolveEndpoint('airshopping', 'TRAVELNDC_AIR_SHOPPING_PATH'),
        'offerprice' => $resolveEndpoint('offerprice', 'TRAVELNDC_OFFER_PRICE_PATH'),
        'ordercreate' => $resolveEndpoint('ordercreate', 'TRAVELNDC_ORDER_CREATE_PATH'),
        'orderretrieve' => $resolveEndpoint('orderretrieve', 'TRAVELNDC_ORDER_RETRIEVE_PATH'),
        'ordercancel' => $resolveEndpoint('ordercancel', 'TRAVELNDC_ORDER_CANCEL_PATH'),
    ],
    'templates' => $templates,
    'demo_responses' => $loader->responses(),
    // Legacy keys retained for compatibility while the application migrates to named endpoints.
    'air_shopping_path' => $resolveEndpoint('airshopping', 'TRAVELNDC_AIR_SHOPPING_PATH'),
    'offer_price_path' => $resolveEndpoint('offerprice', 'TRAVELNDC_OFFER_PRICE_PATH'),
];
