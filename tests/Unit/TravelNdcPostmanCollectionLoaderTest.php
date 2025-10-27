<?php

use App\Services\TravelNDC\PostmanCollectionLoader;

it('parses the TravelNDC Postman collection for endpoints and templates', function () {
    $loader = PostmanCollectionLoader::instance();

    expect($loader->baseUrl())->toBeString()->not->toBe('');

    foreach (['airshopping', 'offerprice', 'ordercreate', 'orderretrieve', 'ordercancel'] as $endpoint) {
        $path = $loader->endpointPath($endpoint);
        expect($path)->toBeString()->not->toBe('');
    }

    $airShoppingTemplate = $loader->template('airshopping');
    $offerPriceTemplate = $loader->template('offerprice');

    expect($airShoppingTemplate)->toContain('<AirShoppingRQ');
    expect($offerPriceTemplate)->toContain('<OfferPriceRQ');

    $airShoppingResponse = $loader->response('airshopping');
    $offerPriceResponse = $loader->response('offerprice');

    expect($airShoppingResponse)->toContain('<AirShoppingRS');
    expect($offerPriceResponse)->toContain('<OfferPriceRS');
});
