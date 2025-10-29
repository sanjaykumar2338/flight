<?php

return [
    'defaults' => [
        'markup_percent' => (float) env('PRICING_DEFAULT_MARKUP_PERCENT', 0),
        'flat_markup' => (float) env('PRICING_DEFAULT_FLAT_MARKUP', 0),
    ],
    'rules' => [
        'enabled' => (bool) env('PRICING_RULES_ENABLED', true),
        'cache_ttl' => (int) env('PRICING_RULES_CACHE_TTL', 300),
    ],
];
