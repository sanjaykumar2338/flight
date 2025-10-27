<?php

return [
    'defaults' => [
        'markup_percent' => (float) env('PRICING_DEFAULT_MARKUP_PERCENT', 0),
        'flat_markup' => (float) env('PRICING_DEFAULT_FLAT_MARKUP', 0),
    ],
];
