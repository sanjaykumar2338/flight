<?php

namespace App\Services\TravelNDC;

use App\DataTransferObjects\FlightSearchData;
use App\Services\TravelNDC\Demo\VidecomDemoProvider;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Support\AirlineDirectory;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TravelNdcService
{
    private const NAMESPACE_URI = 'http://www.iata.org/IATA/EDIST/2017.2';

    /**
     * @var array<string, mixed>
     */
    private array $config;

    private ?VidecomDemoProvider $demoProvider = null;

    public function __construct(private readonly TravelNdcClient $client)
    {
        $this->config = config('travelndc', []);
    }

    /**
     * @return array{offers: array<int, array<string, mixed>>, airlines: array<int, string>}
     */
    public function searchFlights(FlightSearchData $searchData, int $flexibleDays = 0, array $airlineFilters = []): array
    {
        $flexibleDays = max(0, min(3, $flexibleDays));

        if ($this->usingVidecomDemo()) {
            try {
                return $this->searchFlightsViaVidecom($searchData, $flexibleDays, $airlineFilters);
            } catch (TravelNdcException $exception) {
                Log::warning('Videcom demo availability failed, falling back to Postman responses.', [
                    'message' => $exception->getMessage(),
                    'origin' => $searchData->origin,
                    'destination' => $searchData->destination,
                    'departure_date' => $searchData->departureDate->toDateString(),
                    'return_date' => $searchData->returnDate?->toDateString(),
                    'flexible_days' => $flexibleDays,
                    'airline_filters' => $airlineFilters,
                ]);
            }
        }

        $allOffers = [];
        $availableAirlines = [];

        foreach ($this->generateSearchWindows($searchData, $flexibleDays) as $offset => $payload) {
            $requestXml = $this->buildAirShoppingXml($payload, $airlineFilters);
            $responseXml = $this->client->post('airshopping', $requestXml);

            $parsed = $this->parseAirShoppingResponse($responseXml);

            $offers = array_map(function (array $offer) use ($offset, $payload) {
                $offer['day_offset'] = $offset;
                $offer['departure_date'] = $payload->departureDate->toDateString();

                if ($payload->isRoundTrip() && $payload->returnDate) {
                    $offer['return_date'] = $payload->returnDate->toDateString();
                }

                return $offer;
            }, $parsed['offers']);

            $allOffers = array_merge($allOffers, $offers);
            $availableAirlines = array_merge($availableAirlines, $parsed['airlines']);
        }

        $allOffers = collect($allOffers)
            ->sortBy([
                fn ($offer) => $offer['day_offset'] ?? 0,
                fn ($offer) => $offer['pricing']['total_amount'] ?? $offer['pricing']['base_amount'] ?? 0,
            ])
            ->values()
            ->all();

        return [
            'offers' => $allOffers,
            'airlines' => collect($availableAirlines)->filter()->unique()->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $offerPayload
     * @return array<string, mixed>
     */
    public function priceOffer(array $offerPayload): array
    {
        if ($this->usingVidecomDemo() && Arr::get($offerPayload, 'demo_provider') === 'videcom') {
            return $this->priceVidecomOffer($offerPayload);
        }

        foreach (['offer_id', 'owner', 'offer_items'] as $required) {
            if (!Arr::has($offerPayload, $required)) {
                throw new TravelNdcException("Missing {$required} in offer payload.");
            }
        }

        $requestXml = $this->buildOfferPriceXml($offerPayload);
        $responseXml = $this->client->post('offerprice', $requestXml);

        return $this->parseOfferPriceResponse($responseXml);
    }

    private function searchFlightsViaVidecom(FlightSearchData $searchData, int $flexibleDays, array $airlineFilters): array
    {
        $offers = [];
        $airlines = [];

        foreach ($this->generateSearchWindows($searchData, $flexibleDays) as $offset => $window) {
            $result = $this->demoProvider()->search($window, $airlineFilters);

            $windowOffers = array_map(function (array $offer) use ($offset, $window) {
                $offer['day_offset'] = $offset;
                $offer['departure_date'] = $window->departureDate->toDateString();

                if ($window->isRoundTrip() && $window->returnDate) {
                    $offer['return_date'] = $window->returnDate->toDateString();
                }

                return $offer;
            }, $result['offers'] ?? []);

            $offers = array_merge($offers, $windowOffers);
            $airlines = array_merge($airlines, $result['airlines'] ?? []);
        }

        $offers = collect($offers)
            ->sortBy([
                fn ($offer) => $offer['day_offset'] ?? 0,
                fn ($offer) => $offer['pricing']['total_amount'] ?? $offer['pricing']['base_amount'] ?? 0,
            ])
            ->values()
            ->all();

        return [
            'offers' => $offers,
            'airlines' => collect($airlines)->filter()->unique()->values()->all(),
        ];
    }

    private function priceVidecomOffer(array $offerPayload): array
    {
        $pricing = Arr::get($offerPayload, 'ndc_pricing', Arr::get($offerPayload, 'pricing', []));

        if (!is_array($pricing)) {
            throw new TravelNdcException('Demo offer is missing pricing data.');
        }

        $base = round((float) ($pricing['base_amount'] ?? 0), 2);
        $tax = round((float) ($pricing['tax_amount'] ?? 0), 2);
        $total = round((float) ($pricing['total_amount'] ?? ($base + $tax)), 2);

        return [
            'currency' => Arr::get($offerPayload, 'currency', $this->config['currency'] ?? 'USD'),
            'pricing' => [
                'base_amount' => $base,
                'tax_amount' => $tax,
                'total_amount' => $total,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $offerPayload
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    public function holdVidecomOffer(array $offerPayload, array $details): array
    {
        if (!$this->canHoldVidecomOffer($offerPayload)) {
            throw new TravelNdcException('Videcom booking is not available for this offer.');
        }

        return $this->demoProvider()->holdBooking($offerPayload, $details);
    }

    /**
     * @param array<string, mixed> $offerPayload
     */
    public function canHoldVidecomOffer(array $offerPayload): bool
    {
        return $this->usingVidecomDemo()
            && strtolower((string) Arr::get($offerPayload, 'demo_provider')) === 'videcom';
    }

    private function usingVidecomDemo(): bool
    {
        $mode = strtolower((string) ($this->config['mode'] ?? 'sandbox'));
        $provider = strtolower((string) ($this->config['demo_provider'] ?? 'postman'));

        return $mode === 'demo' && $provider === 'videcom';
    }

    private function demoProvider(): VidecomDemoProvider
    {
        if ($this->demoProvider === null) {
            $this->demoProvider = new VidecomDemoProvider(null, config('travelndc.demo_videcom'));
        }

        return $this->demoProvider;
    }

    /**
     * @return array<int, FlightSearchData>
     */
    private function generateSearchWindows(FlightSearchData $searchData, int $flexibleDays): array
    {
        $windows = [0 => $searchData];

        if ($flexibleDays === 0) {
            return $windows;
        }

        for ($day = 1; $day <= $flexibleDays; $day++) {
            $minusDeparture = $searchData->departureDate->copy()->subDays($day);
            $plusDeparture = $searchData->departureDate->copy()->addDays($day);

            $returnMinus = $searchData->returnDate?->copy()->subDays($day);
            $returnPlus = $searchData->returnDate?->copy()->addDays($day);

            $windows[-$day] = $searchData->withAdjustedDates($minusDeparture, $returnMinus);
            $windows[$day] = $searchData->withAdjustedDates($plusDeparture, $returnPlus);
        }

        ksort($windows);

        return $windows;
    }

    private function getTemplate(string $key): string
    {
        $template = $this->config['templates'][$key] ?? null;

        if (!is_string($template) || trim($template) === '') {
            throw new TravelNdcException("Missing XML template for {$key} request.");
        }

        return $template;
    }

    private function loadTemplateDocument(string $template): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        if (!@$doc->loadXML($template)) {
            throw new TravelNdcException('Unable to parse TravelNDC request template.');
        }

        return $doc;
    }

    private function createXPath(DOMDocument $doc, DOMElement $root): DOMXPath
    {
        $xpath = new DOMXPath($doc);
        $namespace = $root->namespaceURI ?: self::NAMESPACE_URI;
        $xpath->registerNamespace('ns', $namespace);

        return $xpath;
    }

    private function setDocumentName(DOMDocument $doc, DOMXPath $xpath): void
    {
        $documentName = $xpath->query('//ns:Document/ns:Name')->item(0);

        if ($documentName instanceof DOMElement) {
            $this->setElementText($doc, $documentName, config('app.name', 'TravelNDC Client'));
        }
    }

    private function applyTravelAgencyDetails(DOMDocument $doc, DOMXPath $xpath): void
    {
        $agencyNode = $xpath->query('//ns:TravelAgencySender')->item(0);

        if (!$agencyNode instanceof DOMElement) {
            return;
        }

        $this->setChildElementValue($doc, $agencyNode, 'AgencyID', $this->config['agency_id'] ?? null);
        $this->setChildElementValue($doc, $agencyNode, 'Name', $this->config['agency_name'] ?? null);
        $this->setChildElementValue($doc, $agencyNode, 'IATA_Number', $this->config['target_branch'] ?? null);

        $agentUserId = $this->config['agent_user_id'] ?? null;
        $agentUserNode = $this->firstChildElement($agencyNode, 'AgentUser');

        if ($agentUserId) {
            if (!$agentUserNode instanceof DOMElement) {
                $agentUserNode = $doc->createElementNS(self::NAMESPACE_URI, 'AgentUser');
                $agencyNode->appendChild($agentUserNode);
            }

            $this->setChildElementValue($doc, $agentUserNode, 'AgentUserID', $agentUserId);
        } elseif ($agentUserNode instanceof DOMElement) {
            $agencyNode->removeChild($agentUserNode);
        }
    }

    private function updateAirlinePreferences(DOMDocument $doc, DOMXPath $xpath, array $airlineFilters): void
    {
        $preferencesNode = $xpath->query('//ns:Preference')->item(0);

        if (!$preferencesNode instanceof DOMElement) {
            return;
        }

        $airlineNode = $this->firstChildElement($preferencesNode, 'AirlinePreferences');

        $codes = array_values(array_unique(array_map('strtoupper', array_filter($airlineFilters))));

        if (empty($codes)) {
            if ($airlineNode instanceof DOMElement) {
                $preferencesNode->removeChild($airlineNode);
            }

            return;
        }

        if (!$airlineNode instanceof DOMElement) {
            $airlineNode = $doc->createElementNS(self::NAMESPACE_URI, 'AirlinePreferences');
            $preferencesNode->insertBefore($airlineNode, $preferencesNode->firstChild);
        } else {
            while ($airlineNode->firstChild) {
                $airlineNode->removeChild($airlineNode->firstChild);
            }
        }

        foreach ($codes as $code) {
            $airline = $doc->createElementNS(self::NAMESPACE_URI, 'Airline');
            $airline->appendChild($this->createElement($doc, 'AirlineID', $code));
            $airlineNode->appendChild($airline);
        }
    }

    private function updateCabinPreference(DOMDocument $doc, DOMXPath $xpath, string $cabinClass): void
    {
        $codeNode = $xpath->query('//ns:Preference//ns:CabinPreferences//ns:CabinType//ns:Code')->item(0);

        if ($codeNode instanceof DOMElement) {
            $this->setElementText($doc, $codeNode, $this->mapCabinToCode($cabinClass));
        }
    }

    private function replaceOriginDestinations(DOMDocument $doc, DOMXPath $xpath, FlightSearchData $searchData): void
    {
        $coreQueryNode = $xpath->query('//ns:CoreQuery')->item(0);
        $root = $doc->documentElement;

        if (!$coreQueryNode instanceof DOMElement) {
            $coreQueryNode = $doc->createElementNS(self::NAMESPACE_URI, 'CoreQuery');
            if ($root instanceof DOMElement) {
                $root->appendChild($coreQueryNode);
            }
        }

        $existing = $this->firstChildElement($coreQueryNode, 'OriginDestinations');

        if ($existing instanceof DOMElement) {
            $coreQueryNode->removeChild($existing);
        }

        $originDestinations = $doc->createElementNS(self::NAMESPACE_URI, 'OriginDestinations');
        $originDestinations->appendChild($this->buildOriginDestination($doc, $searchData->origin, $searchData->destination, $searchData->departureDate));

        if ($searchData->isRoundTrip() && $searchData->returnDate) {
            $originDestinations->appendChild($this->buildOriginDestination($doc, $searchData->destination, $searchData->origin, $searchData->returnDate));
        }

        $coreQueryNode->appendChild($originDestinations);
    }

    private function replacePassengerList(DOMDocument $doc, DOMXPath $xpath, FlightSearchData $searchData): void
    {
        $dataListsNode = $xpath->query('//ns:DataLists')->item(0);
        $root = $doc->documentElement;

        if (!$dataListsNode instanceof DOMElement) {
            $dataListsNode = $doc->createElementNS(self::NAMESPACE_URI, 'DataLists');
            if ($root instanceof DOMElement) {
                $root->appendChild($dataListsNode);
            }
        }

        $passengerList = $this->firstChildElement($dataListsNode, 'PassengerList');

        if ($passengerList instanceof DOMElement) {
            $dataListsNode->removeChild($passengerList);
        }

        $passengerList = $doc->createElementNS(self::NAMESPACE_URI, 'PassengerList');
        $index = 1;

        foreach ($searchData->passengers() as $passenger) {
            for ($i = 0; $i < $passenger['count']; $i++) {
                $id = sprintf('%s%d', $passenger['type'], $index++);
                $passengerElement = $doc->createElementNS(self::NAMESPACE_URI, 'Passenger');
                $passengerElement->setAttribute('PassengerID', $id);
                $passengerElement->appendChild($this->createElement($doc, 'PTC', $passenger['type']));
                $passengerList->appendChild($passengerElement);
            }
        }

        $dataListsNode->appendChild($passengerList);
    }

    private function updateRecipientAirline(DOMDocument $doc, DOMXPath $xpath, ?string $owner): void
    {
        $recipientNode = $xpath->query('//ns:Party/ns:Recipient')->item(0);

        if (!$recipientNode instanceof DOMElement) {
            return;
        }

        if (!$owner) {
            $recipientNode->parentNode?->removeChild($recipientNode);
            return;
        }

        $oraRecipient = $this->firstChildElement($recipientNode, 'ORA_Recipient');

        if (!$oraRecipient instanceof DOMElement) {
            $oraRecipient = $doc->createElementNS(self::NAMESPACE_URI, 'ORA_Recipient');
            $recipientNode->appendChild($oraRecipient);
        }

        $this->setChildElementValue($doc, $oraRecipient, 'AirlineID', strtoupper($owner));
    }

    private function applyOfferDetails(DOMDocument $doc, DOMXPath $xpath, array $offerPayload): void
    {
        $offerNode = $xpath->query('//ns:Query/ns:Offer')->item(0);

        if (!$offerNode instanceof DOMElement) {
            return;
        }

        $offerNode->setAttribute('OfferID', (string) Arr::get($offerPayload, 'offer_id'));
        $offerNode->setAttribute('Owner', (string) Arr::get($offerPayload, 'owner'));

        if ($responseId = Arr::get($offerPayload, 'response_id')) {
            $offerNode->setAttribute('ResponseID', (string) $responseId);
        } else {
            $offerNode->removeAttribute('ResponseID');
        }

        while ($offerNode->firstChild) {
            $offerNode->removeChild($offerNode->firstChild);
        }

        foreach (Arr::get($offerPayload, 'offer_items', []) as $item) {
            $offerItem = $doc->createElementNS(self::NAMESPACE_URI, 'OfferItem');
            $offerItem->setAttribute('OfferItemID', (string) Arr::get($item, 'offer_item_id'));

            $passengerRefs = array_filter(array_map('trim', (array) Arr::get($item, 'passenger_refs', [])));
            if (!empty($passengerRefs)) {
                $offerItem->appendChild($this->createElement($doc, 'PassengerRefs', implode(' ', $passengerRefs)));
            }

            $segmentRefs = array_filter(array_map('trim', (array) Arr::get($item, 'segment_refs', [])));
            if (!empty($segmentRefs)) {
                $offerItem->appendChild($this->createElement($doc, 'SegmentRefs', implode(' ', $segmentRefs)));
            }

            $offerNode->appendChild($offerItem);
        }
    }

    private function replaceOfferPassengerList(DOMDocument $doc, DOMXPath $xpath, array $offerPayload): void
    {
        $dataListsNode = $xpath->query('//ns:DataLists')->item(0);

        if (!$dataListsNode instanceof DOMElement) {
            return;
        }

        $passengerList = $this->firstChildElement($dataListsNode, 'PassengerList');
        if ($passengerList instanceof DOMElement) {
            $dataListsNode->removeChild($passengerList);
        }

        $passengers = $this->collectOfferPassengers($offerPayload);

        if (empty($passengers)) {
            return;
        }

        $passengerList = $doc->createElementNS(self::NAMESPACE_URI, 'PassengerList');

        foreach ($passengers as $ref => $ptc) {
            $passengerElement = $doc->createElementNS(self::NAMESPACE_URI, 'Passenger');
            $passengerElement->setAttribute('PassengerID', $ref);
            $passengerElement->appendChild($this->createElement($doc, 'PTC', $ptc));
            $passengerList->appendChild($passengerElement);
        }

        $dataListsNode->appendChild($passengerList);
    }

    private function buildOriginDestination(DOMDocument $doc, string $origin, string $destination, Carbon $date): DOMElement
    {
        $originDestination = $doc->createElementNS(self::NAMESPACE_URI, 'OriginDestination');

        $departure = $doc->createElementNS(self::NAMESPACE_URI, 'Departure');
        $departure->appendChild($this->createElement($doc, 'AirportCode', strtoupper($origin)));
        $departure->appendChild($this->createElement($doc, 'Date', $date->toDateString()));
        $originDestination->appendChild($departure);

        $arrival = $doc->createElementNS(self::NAMESPACE_URI, 'Arrival');
        $arrival->appendChild($this->createElement($doc, 'AirportCode', strtoupper($destination)));
        $originDestination->appendChild($arrival);

        return $originDestination;
    }

    private function createElement(DOMDocument $doc, string $name, ?string $value = null): DOMElement
    {
        $element = $doc->createElementNS(self::NAMESPACE_URI, $name);

        if ($value !== null && $value !== '') {
            $element->appendChild($doc->createTextNode($value));
        }

        return $element;
    }

    private function setChildElementValue(DOMDocument $doc, DOMElement $parent, string $localName, ?string $value): void
    {
        $child = $this->firstChildElement($parent, $localName);

        if ($value === null || $value === '') {
            if ($child instanceof DOMElement) {
                $parent->removeChild($child);
            }

            return;
        }

        if (!$child instanceof DOMElement) {
            $child = $doc->createElementNS(self::NAMESPACE_URI, $localName);
            $parent->appendChild($child);
        }

        $this->setElementText($doc, $child, $value);
    }

    private function setElementText(DOMDocument $doc, DOMElement $element, string $value): void
    {
        while ($element->firstChild) {
            $element->removeChild($element->firstChild);
        }

        $element->appendChild($doc->createTextNode($value));
    }

    private function firstChildElement(DOMElement $parent, string $localName): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && strcasecmp($child->localName, $localName) === 0) {
                return $child;
            }
        }

        return null;
    }

    private function collectOfferPassengers(array $offerPayload): array
    {
        $passengers = [];

        foreach (Arr::get($offerPayload, 'offer_items', []) as $item) {
            $refs = Arr::get($item, 'passenger_refs', []);

            if (!is_array($refs)) {
                continue;
            }

            foreach ($refs as $ref) {
                $ref = trim((string) $ref);

                if ($ref === '') {
                    continue;
                }

                $passengers[$ref] = $this->inferPassengerTypeFromReference($ref);
            }
        }

        return $passengers;
    }

    private function inferPassengerTypeFromReference(string $reference): string
    {
        if (preg_match('/^[A-Z]{3}/', strtoupper($reference), $match)) {
            $prefix = strtoupper($match[0]);

            if (in_array($prefix, ['ADT', 'CHD', 'INF'], true)) {
                return $prefix;
            }
        }

        if (preg_match('/^[A-Z]+/', strtoupper($reference), $match)) {
            $prefix = strtoupper($match[0]);

            if (in_array($prefix, ['ADT', 'CHD', 'INF'], true)) {
                return $prefix;
            }
        }

        return 'ADT';
    }

    private function buildAirShoppingXml(FlightSearchData $searchData, array $airlineFilters = []): string
    {
        $doc = $this->loadTemplateDocument($this->getTemplate('airshopping'));

        $root = $doc->documentElement;

        if (!$root instanceof DOMElement) {
            throw new TravelNdcException('AirShopping template is invalid.');
        }

        $xpath = $this->createXPath($doc, $root);

        $this->setDocumentName($doc, $xpath);
        $this->applyTravelAgencyDetails($doc, $xpath);
        $this->updateAirlinePreferences($doc, $xpath, $airlineFilters);
        $this->updateCabinPreference($doc, $xpath, $searchData->cabinClass);
        $this->replaceOriginDestinations($doc, $xpath, $searchData);
        $this->replacePassengerList($doc, $xpath, $searchData);

        return $doc->saveXML() ?: '';
    }

    /**
     * @param array<string, mixed> $offerPayload
     */
    private function buildOfferPriceXml(array $offerPayload): string
    {
        $doc = $this->loadTemplateDocument($this->getTemplate('offerprice'));

        $root = $doc->documentElement;

        if (!$root instanceof DOMElement) {
            throw new TravelNdcException('OfferPrice template is invalid.');
        }

        $xpath = $this->createXPath($doc, $root);

        $this->setDocumentName($doc, $xpath);
        $this->applyTravelAgencyDetails($doc, $xpath);
        $this->updateRecipientAirline($doc, $xpath, Arr::get($offerPayload, 'owner'));
        $this->applyOfferDetails($doc, $xpath, $offerPayload);
        $this->replaceOfferPassengerList($doc, $xpath, $offerPayload);

        return $doc->saveXML() ?: '';
    }

    /**
     * @return array{offers: array<int, array<string, mixed>>, airlines: array<int, string>}
     */
    private function parseAirShoppingResponse(string $xmlContent): array
    {
        $dom = new DOMDocument();

        if (!@$dom->loadXML($xmlContent)) {
            throw new TravelNdcException('Unable to parse AirShopping response (invalid XML).');
        }

        $xpath = new DOMXPath($dom);
        $namespace = $dom->documentElement?->namespaceURI ?? self::NAMESPACE_URI;
        $xpath->registerNamespace('ns', $namespace);

        $this->assertNoErrors($xpath);

        $segmentMap = $this->extractSegments($xpath);
        $pricingOffers = [];
        $airlines = [];

        $offerNodes = $xpath->query('//ns:AirOffer | //ns:AirlineOffer');

        if (!$offerNodes || $offerNodes->count() === 0) {
            return [
                'offers' => [],
                'airlines' => [],
            ];
        }

        /** @var DOMElement $offerNode */
        foreach ($offerNodes as $offerNode) {
            $offerId = trim($offerNode->getAttribute('OfferID') ?: $offerNode->getAttribute('OfferIDRef'));
            $owner = trim($offerNode->getAttribute('Owner') ?: (string) Arr::get($segmentMap, 'default_owner', 'UNKNOWN'));
            $responseId = trim($offerNode->getAttribute('ResponseID'));

            $baseAmount = $this->firstNumeric($xpath, $offerNode, [
                './/ns:TotalAmount/ns:Service/ns:SimpleCurrencyPrice',
                './/ns:TotalAmount/ns:SimpleCurrencyPrice',
                './/ns:Total/ns:SimpleCurrencyPrice',
                './/ns:Amount/ns:SimpleCurrencyPrice',
            ]);

            $currency = $this->firstCurrency($xpath, $offerNode, [
                './/ns:TotalAmount/ns:SimpleCurrencyPrice',
                './/ns:Total/ns:SimpleCurrencyPrice',
                './/ns:Amount/ns:SimpleCurrencyPrice',
            ]);

            $taxAmount = $this->firstNumeric($xpath, $offerNode, [
                './/ns:TaxAmounts/ns:Total/ns:SimpleCurrencyPrice',
                './/ns:TaxAmount/ns:SimpleCurrencyPrice',
            ]);

            $offerItems = $this->extractOfferItems($xpath, $offerNode);

            $offerSegments = collect($offerItems)
                ->flatMap(fn ($item) => $item['segment_refs'])
                ->unique()
                ->map(fn ($reference) => $segmentMap[$reference] ?? null)
                ->filter()
                ->values()
                ->all();

            $primaryCarrier = trim((string) ($offerSegments[0]['marketing_carrier'] ?? ($offerItems[0]['carrier'] ?? $owner)));
            $carrierName = AirlineDirectory::name($primaryCarrier, $primaryCarrier);

            $pricingOffers[] = [
                'offer_id' => $offerId,
                'owner' => $owner,
                'response_id' => $responseId !== '' ? $responseId : null,
                'currency' => $currency,
                'pricing' => [
                    'base_amount' => $baseAmount,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $baseAmount + $taxAmount,
                ],
                'segments' => $offerSegments,
                'offer_items' => $offerItems,
                'primary_carrier' => $primaryCarrier,
                'airline_name' => $carrierName,
            ];

            $airlines[] = $primaryCarrier;
        }

        return [
            'offers' => $pricingOffers,
            'airlines' => $airlines,
        ];
    }

    private function parseOfferPriceResponse(string $xmlContent): array
    {
        $dom = new DOMDocument();

        if (!@$dom->loadXML($xmlContent)) {
            throw new TravelNdcException('Unable to parse OfferPrice response (invalid XML).');
        }

        $xpath = new DOMXPath($dom);
        $namespace = $dom->documentElement?->namespaceURI ?? self::NAMESPACE_URI;
        $xpath->registerNamespace('ns', $namespace);

        $this->assertNoErrors($xpath);

        $total = $this->firstNumeric($xpath, $dom->documentElement, [
            '//ns:PricedOffer/ns:Price/ns:TotalAmount/ns:SimpleCurrencyPrice',
            '//ns:TotalAmount/ns:SimpleCurrencyPrice',
        ]);
        $currency = $this->firstCurrency($xpath, $dom->documentElement, [
            '//ns:PricedOffer/ns:Price/ns:TotalAmount/ns:SimpleCurrencyPrice',
            '//ns:TotalAmount/ns:SimpleCurrencyPrice',
        ]);
        $base = $this->firstNumeric($xpath, $dom->documentElement, [
            '//ns:PricedOffer/ns:Price/ns:BaseAmount/ns:SimpleCurrencyPrice',
            '//ns:BaseAmount/ns:SimpleCurrencyPrice',
        ]);
        $tax = $this->firstNumeric($xpath, $dom->documentElement, [
            '//ns:PricedOffer/ns:Price/ns:TaxAmount/ns:SimpleCurrencyPrice',
            '//ns:TaxAmount/ns:SimpleCurrencyPrice',
        ]);

        return [
            'currency' => $currency,
            'pricing' => [
                'base_amount' => $base,
                'tax_amount' => $tax,
                'total_amount' => $total ?: ($base + $tax),
            ],
        ];
    }

    private function mapCabinToCode(string $cabinClass): string
    {
        return match (Str::upper($cabinClass)) {
            'FIRST' => '1',
            'BUSINESS' => '3',
            'PREMIUM_ECONOMY' => '4',
            default => '5', // ECONOMY or fallback
        };
    }

    private function firstNumeric(DOMXPath $xpath, DOMElement $context, array $expressions): float
    {
        foreach ($expressions as $expression) {
            $nodes = $xpath->evaluate($expression, $context);

            if ($nodes instanceof \DOMNodeList && $nodes->length > 0) {
                $value = trim($nodes->item(0)?->textContent ?? '');

                if ($value !== '') {
                    return (float) $value;
                }
            }
        }

        return 0.0;
    }

    private function firstCurrency(DOMXPath $xpath, DOMElement $context, array $expressions): string
    {
        foreach ($expressions as $expression) {
            $nodes = $xpath->evaluate($expression, $context);

            if ($nodes instanceof \DOMNodeList && $nodes->length > 0) {
                $currency = $nodes->item(0)?->attributes?->getNamedItem('CurrencyCode')?->textContent;
                $currency ??= $nodes->item(0)?->attributes?->getNamedItem('Code')?->textContent;

                if ($currency) {
                    return strtoupper($currency);
                }
            }
        }

        return strtoupper($this->config['currency'] ?? 'USD');
    }

    private function assertNoErrors(DOMXPath $xpath): void
    {
        $errors = $xpath->query('//ns:Errors/ns:Error | //ns:Error');

        if ($errors && $errors->length > 0) {
            $messages = [];

            foreach ($errors as $error) {
                $code = $error->attributes?->getNamedItem('Code')?->textContent;
                $messages[] = trim($error->textContent) . ($code ? " ({$code})" : '');
            }

            throw new TravelNdcException('TravelNDC error: ' . implode('; ', array_filter($messages)));
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function extractSegments(DOMXPath $xpath): array
    {
        $segments = [];

        $segmentNodes = $xpath->query('//ns:FlightSegment');

        if (!$segmentNodes) {
            return $segments;
        }

        /** @var DOMElement $segment */
        foreach ($segmentNodes as $segment) {
            $segmentKey = $segment->getAttribute('SegmentKey') ?: $segment->getAttribute('SegmentID');

            if (!$segmentKey) {
                continue;
            }

            $departureDate = $this->firstText($xpath, $segment, ['ns:Departure/ns:Date']);
            $departureTime = $this->firstText($xpath, $segment, ['ns:Departure/ns:Time']);
            $arrivalDate = $this->firstText($xpath, $segment, ['ns:Arrival/ns:Date']);
            $arrivalTime = $this->firstText($xpath, $segment, ['ns:Arrival/ns:Time']);

            $segments[$segmentKey] = [
                'segment_key' => $segmentKey,
                'origin' => $this->firstText($xpath, $segment, ['ns:Departure/ns:AirportCode']),
                'destination' => $this->firstText($xpath, $segment, ['ns:Arrival/ns:AirportCode']),
                'departure' => $this->combineDateTime($departureDate, $departureTime),
                'arrival' => $this->combineDateTime($arrivalDate, $arrivalTime),
                'marketing_carrier' => $this->firstText($xpath, $segment, ['ns:MarketingCarrier/ns:AirlineID']),
                'marketing_flight_number' => $this->firstText($xpath, $segment, ['ns:MarketingCarrier/ns:FlightNumber']),
                'operating_carrier' => $this->firstText($xpath, $segment, ['ns:OperatingCarrier/ns:AirlineID']),
                'equipment' => $this->firstText($xpath, $segment, ['ns:Equipment']),
                'duration' => $this->firstText($xpath, $segment, ['ns:FlightDuration']),
            ];
        }

        return $segments;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractOfferItems(DOMXPath $xpath, DOMElement $offerNode): array
    {
        $items = [];

        $itemNodes = $xpath->query('.//ns:OfferItem', $offerNode);

        if (!$itemNodes) {
            return $items;
        }

        /** @var DOMElement $itemNode */
        foreach ($itemNodes as $itemNode) {
            $passengerRefsRaw = $this->firstText($xpath, $itemNode, ['ns:PassengerRefs']);
            $segmentRefs = [];
            $segmentReferenceNodes = $xpath->query('.//ns:SegmentReference', $itemNode);

            if ($segmentReferenceNodes && $segmentReferenceNodes->length > 0) {
                /** @var DOMElement $segmentReference */
                foreach ($segmentReferenceNodes as $segmentReference) {
                    $refText = trim($segmentReference->textContent);

                    if ($refText !== '') {
                        $segmentRefs[] = $refText;
                    }
                }
            } else {
                $segmentRefsRaw = $this->firstText($xpath, $itemNode, ['ns:SegmentRefs']);
                $segmentRefs = $this->splitReferences($segmentRefsRaw);
            }

            $passengerRefs = $this->splitReferences($passengerRefsRaw);

            $items[] = [
                'offer_item_id' => $itemNode->getAttribute('OfferItemID') ?: $itemNode->getAttribute('OfferItemIDRef'),
                'passenger_refs' => $passengerRefs,
                'segment_refs' => $segmentRefs,
                'carrier' => $this->firstText($xpath, $itemNode, ['ns:Service/ns:Carrier/ns:AirlineID']),
            ];
        }

        return $items;
    }

    private function firstText(DOMXPath $xpath, DOMElement $context, array $expressions): ?string
    {
        foreach ($expressions as $expression) {
            $nodes = $xpath->evaluate($expression, $context);

            if ($nodes instanceof \DOMNodeList && $nodes->length > 0) {
                $value = trim($nodes->item(0)?->textContent ?? '');

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function splitReferences(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        return preg_split('/\s+/', trim($raw)) ?: [];
    }

    private function combineDateTime(?string $date, ?string $time): ?string
    {
        if (!$date) {
            return null;
        }

        $time = $time ?: '00:00:00';

        try {
            return Carbon::parse("{$date} {$time}")->toIso8601String();
        } catch (\Throwable) {
            return "{$date}T{$time}";
        }
    }
}
