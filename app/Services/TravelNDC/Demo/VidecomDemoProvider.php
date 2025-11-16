<?php

namespace App\Services\TravelNDC\Demo;

use App\DataTransferObjects\FlightSearchData;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Support\AirlineDirectory;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class VidecomDemoProvider
{
    private Client $client;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    private string $endpoint;

    private string $token;

    private string $currency;

    private float $defaultBaseFare;

    private float $farePerMinute;

    private float $defaultTaxPercent;

    public function __construct(?Client $client = null, ?array $config = null)
    {
        $this->config = $config ?? config('travelndc.demo_videcom', []);
        $this->endpoint = trim((string) Arr::get($this->config, 'endpoint', ''));
        $this->token = trim((string) Arr::get($this->config, 'token', ''));

        if ($this->endpoint === '' || $this->token === '') {
            throw new TravelNdcException('Videcom demo provider requires endpoint and token.');
        }

        $this->currency = (string) Arr::get(
            $this->config,
            'currency',
            config('travelndc.currency', 'USD')
        );
        $this->defaultBaseFare = (float) Arr::get($this->config, 'default_base_fare', 0.0);
        $this->farePerMinute = (float) Arr::get($this->config, 'fare_per_minute', 0.0);
        $this->defaultTaxPercent = (float) Arr::get($this->config, 'default_tax_percent', 0.0);

        $this->client = $client ?? new Client([
            'timeout' => (int) Arr::get($this->config, 'timeout', 30),
            'verify' => (bool) Arr::get($this->config, 'verify_ssl', false),
        ]);
    }

    /**
     * @return array{offers: array<int, array<string, mixed>>, airlines: array<int, string>}
     */
    public function search(FlightSearchData $searchData, array $airlineFilters = []): array
    {
        $passengerRefs = $this->buildPassengerRefs($searchData);

        $outbound = $this->fetchLeg(
            $searchData->origin,
            $searchData->destination,
            $searchData->departureDate,
            $airlineFilters
        );

        $offers = $this->buildOffersFromLeg($outbound, $passengerRefs);
        $airlines = collect($outbound)->pluck('airline');

        if ($searchData->isRoundTrip() && $searchData->returnDate) {
            $inbound = $this->fetchLeg(
                $searchData->destination,
                $searchData->origin,
                $searchData->returnDate,
                $airlineFilters
            );

            if (!empty($inbound)) {
                $offers = $this->combineRoundTripOffers($outbound, $inbound, $passengerRefs);
                $airlines = $airlines->merge(collect($inbound)->pluck('airline'));
            }
        }

        return [
            'offers' => $offers,
            'airlines' => $airlines->filter()->unique()->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $offerPayload
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    public function holdBooking(array $offerPayload, array $details): array
    {
        $segments = Arr::get($offerPayload, 'segments', []);

        if (!is_array($segments) || empty($segments)) {
            throw new TravelNdcException('Offer is missing flight segments for Videcom booking.');
        }

        $commands = $this->prepareHoldCommands($segments, $details);
        $response = $this->sendCommandStack($commands);

        if ($response === '') {
            throw new TravelNdcException('Videcom booking response was empty.');
        }

        $decoded = $this->decodeJson($response);

        if (is_array($decoded) && isset($decoded['PNR'])) {
            return $decoded['PNR'];
        }

        if (str_starts_with(ltrim($response), '<?xml')) {
            return ['xml' => $response];
        }

        throw new TravelNdcException(
            'Videcom booking failed: ' . mb_strimwidth($response, 0, 200, '...')
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchLeg(string $origin, string $destination, Carbon $date, array $airlineFilters): array
    {
        $command = $this->buildCommand($date, $origin, $destination);
        $raw = $this->executeCommand($command);
        $parsed = $this->parseFlights($raw);

        $filters = array_map('strtoupper', array_filter($airlineFilters));
        $results = [];

        foreach ($parsed as $flight) {
            if (!$this->matchesRoute($flight, $origin, $destination)) {
                continue;
            }

            if (!empty($filters) && !in_array(strtoupper((string) $flight['airline']), $filters, true)) {
                continue;
            }

            $segment = $this->mapFlightToSegment($flight);

            if ($segment === null) {
                continue;
            }

            $results[] = [
                'segment' => $segment['segment'],
                'duration_minutes' => $segment['duration_minutes'],
                'airline' => strtoupper((string) $flight['airline']),
            ];
        }

        return $results;
    }

    /**
     * @param array<int, array<string, mixed>> $legs
     * @return array<int, array<string, mixed>>
     */
    private function buildOffersFromLeg(array $legs, array $passengerRefs): array
    {
        $offers = [];

        foreach ($legs as $leg) {
            $offers[] = $this->formatOffer([$leg], $passengerRefs);
        }

        return $offers;
    }

    /**
     * @param array<int, array<string, mixed>> $outbound
     * @param array<int, array<string, mixed>> $inbound
     * @return array<int, array<string, mixed>>
     */
    private function combineRoundTripOffers(array $outbound, array $inbound, array $passengerRefs): array
    {
        $offers = [];

        foreach ($outbound as $outLeg) {
            foreach ($inbound as $inLeg) {
                $offers[] = $this->formatOffer([$outLeg, $inLeg], $passengerRefs);
            }
        }

        return $offers;
    }

    /**
     * @param array<int, array<string, mixed>> $legs
     */
    private function formatOffer(array $legs, array $passengerRefs): array
    {
        $segments = array_map(static fn ($leg) => $leg['segment'], $legs);
        $carrier = $segments[0]['marketing_carrier'] ?? ($legs[0]['airline'] ?? 'XX');
        $offerId = $this->buildOfferId($segments);
        $pricing = $this->mergePricing(array_map(
            fn ($leg) => $this->priceForDuration((int) ($leg['duration_minutes'] ?? 0)),
            $legs
        ));

        return [
            'offer_id' => $offerId,
            'owner' => $carrier,
            'response_id' => null,
            'currency' => $this->currency,
            'pricing' => $pricing,
            'ndc_pricing' => $pricing,
            'segments' => $segments,
            'offer_items' => [[
                'offer_item_id' => $offerId . '-ITEM',
                'segment_refs' => array_map(static fn ($segment) => $segment['segment_key'], $segments),
                'passenger_refs' => $passengerRefs,
                'carrier' => $carrier,
            ]],
            'primary_carrier' => $carrier,
            'airline_name' => AirlineDirectory::name($carrier, $carrier),
            'demo_provider' => 'videcom',
            'source' => 'videcom-demo',
        ];
    }

    private function buildCommand(Carbon $date, string $origin, string $destination): string
    {
        $dateCode = strtoupper($date->copy()->setTimezone('UTC')->format('dM'));

        return sprintf('A%s%s%s', $dateCode, strtoupper($origin), strtoupper($destination));
    }

    private function executeCommand(string $command): string
    {
        $soapXml = $this->buildSoapEnvelope($command);

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '"http://videcom.com/PostVRSCommand"',
                ],
                'body' => $soapXml,
            ]);
        } catch (GuzzleException $exception) {
            throw new TravelNdcException(
                'Videcom command request failed: ' . $exception->getMessage(),
                previous: $exception
            );
        }

        $contents = (string) $response->getBody();

        if ($contents === '') {
            throw new TravelNdcException('Videcom command response was empty.');
        }

        $result = $this->extractResultPayload($contents);

        if ($result === null) {
            throw new TravelNdcException('Unable to parse Videcom command payload.');
        }

        return $result;
    }

    private function buildSoapEnvelope(string $command): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <Command xmlns="http://videcom.com/">{$command}</Command>
    <Token xmlns="http://videcom.com/">{$this->token}</Token>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function extractResultPayload(string $contents): ?string
    {
        if (preg_match('/<PostVRSCommandResult[^>]*>(.*?)<\/PostVRSCommandResult>/s', $contents, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_XML1));
        }

        return null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseFlights(string $raw): array
    {
        $flights = [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];

        $pattern = '/^(\d+)\s+([A-Z]{6})\s+([0-9]{2}[A-Z]{3}[0-9]{2})\s+([0-9]{4})\s+([0-9]{2}[A-Z]{3}[0-9]{2})\s+([0-9]{4})\s+([A-Z0-9]{2})\s+([A-Z0-9]{1,5})\s+([A-Z0-9]{3})\s+(.*)$/';

        foreach ($lines as $line) {
            $candidate = trim($line);

            if ($candidate === '') {
                continue;
            }

            if (preg_match($pattern, $candidate, $parts)) {
                $flights[] = [
                    'line_number' => $parts[1],
                    'route' => $parts[2],
                    'depart_date' => $parts[3],
                    'depart_time' => $parts[4],
                    'arrive_date' => $parts[5],
                    'arrive_time' => $parts[6],
                    'airline' => $parts[7],
                    'flight_number' => $parts[8],
                    'aircraft' => $parts[9],
                    'availability' => trim($parts[10]),
                ];
            }
        }

        return $flights;
    }

    private function matchesRoute(array $flight, string $origin, string $destination): bool
    {
        $route = strtoupper((string) ($flight['route'] ?? ''));

        if (strlen($route) !== 6) {
            return true;
        }

        $from = substr($route, 0, 3);
        $to = substr($route, 3, 3);

        return $from === strtoupper($origin) && $to === strtoupper($destination);
    }

    /**
     * @return array{segment: array<string, mixed>, duration_minutes: int}|null
     */
    private function mapFlightToSegment(array $flight): ?array
    {
        $route = (string) ($flight['route'] ?? '');
        $origin = strtoupper(substr($route, 0, 3));
        $destination = strtoupper(substr($route, 3, 3));

        $departureDate = $this->parseDateCode($flight['depart_date'] ?? null);
        $arrivalDate = $this->parseDateCode($flight['arrive_date'] ?? null);

        $departure = $this->applyTime($departureDate, $flight['depart_time'] ?? null);
        $arrival = $this->applyTime($arrivalDate, $flight['arrive_time'] ?? null);

        if (!$departure || !$origin || !$destination) {
            return null;
        }

        $durationMinutes = $this->calculateDurationMinutes($departure, $arrival);
        $segmentKey = $this->buildSegmentKey($flight, $departure);

        return [
            'segment' => [
                'segment_key' => $segmentKey,
                'origin' => $origin,
                'destination' => $destination,
                'departure' => $departure->toIso8601String(),
                'arrival' => $arrival?->toIso8601String(),
                'duration' => $this->formatDuration($durationMinutes),
                'marketing_carrier' => strtoupper((string) ($flight['airline'] ?? '')),
                'marketing_flight_number' => (string) ($flight['flight_number'] ?? ''),
                'operating_carrier' => strtoupper((string) ($flight['airline'] ?? '')),
                'equipment' => (string) ($flight['aircraft'] ?? ''),
                'availability' => $this->parseAvailability($flight['availability'] ?? ''),
            ],
            'duration_minutes' => $durationMinutes,
        ];
    }

    private function parseDateCode(?string $code): ?Carbon
    {
        if (!$code || !preg_match('/^(\d{2})([A-Z]{3})(\d{2})$/', strtoupper($code), $parts)) {
            return null;
        }

        $day = (int) $parts[1];
        $month = ucfirst(strtolower($parts[2]));
        $year = 2000 + (int) $parts[3];
        $formatted = sprintf('%02d-%s-%04d', $day, $month, $year);

        return Carbon::createFromFormat('d-M-Y', $formatted, 'UTC');
    }

    private function applyTime(?Carbon $date, ?string $time): ?Carbon
    {
        if (!$date) {
            return null;
        }

        if (!preg_match('/^(\d{2})(\d{2})$/', (string) $time, $parts)) {
            return $date->copy();
        }

        return $date->copy()->setTime((int) $parts[1], (int) $parts[2], 0);
    }

    private function calculateDurationMinutes(Carbon $departure, ?Carbon $arrival): int
    {
        if (!$arrival) {
            return 0;
        }

        $minutes = $departure->diffInMinutes($arrival, false);

        return $minutes > 0 ? $minutes : 0;
    }

    private function formatDuration(int $minutes): ?string
    {
        if ($minutes <= 0) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('PT%dH%dM', $hours, $mins);
    }

    private function buildSegmentKey(array $flight, Carbon $departure): string
    {
        $airline = strtoupper((string) ($flight['airline'] ?? 'XX'));
        $flightNumber = (string) ($flight['flight_number'] ?? '0000');

        return sprintf('%s-%s-%s', $airline, $flightNumber, $departure->format('YmdHi'));
    }

    /**
     * @return array<string, int>
     */
    private function parseAvailability(string $value): array
    {
        $availability = [];
        $tokens = preg_split('/\s+/', trim($value)) ?: [];

        foreach ($tokens as $token) {
            if (preg_match('/^([A-Z])(\d+)/', strtoupper($token), $parts)) {
                $availability[$parts[1]] = (int) $parts[2];
            }
        }

        return $availability;
    }

    /**
     * @param array<int, array<string, float>> $legsPricing
     * @return array{base_amount: float, tax_amount: float, total_amount: float}
     */
    private function mergePricing(array $legsPricing): array
    {
        $base = 0.0;
        $tax = 0.0;

        foreach ($legsPricing as $pricing) {
            $base += (float) ($pricing['base_amount'] ?? 0);
            $tax += (float) ($pricing['tax_amount'] ?? 0);
        }

        $base = round($base, 2);
        $tax = round($tax, 2);

        return [
            'base_amount' => $base,
            'tax_amount' => $tax,
            'total_amount' => round($base + $tax, 2),
        ];
    }

    /**
     * @return array{base_amount: float, tax_amount: float, total_amount: float}
     */
    private function priceForDuration(int $minutes): array
    {
        $base = max(0.0, $this->defaultBaseFare);

        if ($this->farePerMinute > 0 && $minutes > 0) {
            $base += $minutes * $this->farePerMinute;
        }

        $base = round($base, 2);
        $tax = $this->defaultTaxPercent > 0
            ? round($base * ($this->defaultTaxPercent / 100), 2)
            : 0.0;

        return [
            'base_amount' => $base,
            'tax_amount' => $tax,
            'total_amount' => round($base + $tax, 2),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildPassengerRefs(FlightSearchData $searchData): array
    {
        $refs = [];
        $groups = [
            'ADT' => max(0, (int) $searchData->adults),
            'CHD' => max(0, (int) $searchData->children),
            'INF' => max(0, (int) $searchData->infants),
        ];

        foreach ($groups as $type => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $refs[] = sprintf('%s%d', $type, $i);
            }
        }

        if (empty($refs)) {
            $refs[] = 'ADT1';
        }

        return $refs;
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     */
    private function buildOfferId(array $segments): string
    {
        $first = $segments[0] ?? [];
        $carrier = strtoupper((string) ($first['marketing_carrier'] ?? 'VC'));
        $fingerprint = substr(md5(json_encode($segments, JSON_UNESCAPED_SLASHES)), 0, 8);

        return sprintf('VIDECOM-%s-%s', $carrier, $fingerprint);
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     * @param array<string, mixed> $details
     * @return array<int, string>
     */
    private function prepareHoldCommands(array $segments, array $details): array
    {
        $commands = ['i'];

        $commands[] = $this->formatPassengerCommand(
            (string) Arr::get($details, 'passenger_last_name', Arr::get($details, 'last_name', '')),
            (string) Arr::get($details, 'passenger_first_name', Arr::get($details, 'first_name', '')),
            (string) Arr::get($details, 'passenger_title', Arr::get($details, 'title', 'MR'))
        );
        $commands[] = $this->formatEmailCommand((string) Arr::get($details, 'contact_email', Arr::get($details, 'email', '')));
        $commands[] = $this->formatPhoneCommand((string) Arr::get($details, 'contact_phone', Arr::get($details, 'phone', '')));

        $seatCount = max(1, (int) Arr::get($details, 'seat_count', 1));
        $preferredClass = Arr::get($details, 'booking_class');

        foreach ($segments as $segment) {
            $commands[] = $this->buildSellCommand($segment, $preferredClass, $seatCount);
        }

        $commands[] = 'FG';
        $commands[] = 'FS1';
        $commands[] = '8C';
        $commands[] = 'e*r~x';

        return array_values(array_filter($commands));
    }

    private function sendCommandStack(array $commands): string
    {
        $stack = implode('^', $commands);

        try {
            return $this->executeCommand($stack);
        } catch (TravelNdcException $exception) {
            throw new TravelNdcException(
                'Videcom booking command failed: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    private function formatPassengerCommand(string $lastName, string $firstName, string $title): string
    {
        $last = $lastName !== '' ? Str::upper(preg_replace('/[^A-Z]/i', '', $lastName) ?: $lastName) : 'PAX';
        $first = $firstName !== '' ? Str::upper(preg_replace('/[^A-Z]/i', '', $firstName) ?: $firstName) : 'GUEST';
        $resolvedTitle = Str::upper(trim($title ?: 'MR'));

        return sprintf('-1%s/%s %s', $last, $first, $resolvedTitle);
    }

    private function formatEmailCommand(string $email): string
    {
        $sanitized = Str::upper(trim($email));

        return sprintf('9-1E*%s', $sanitized !== '' ? $sanitized : 'UNKNOWN@EMAIL.COM');
    }

    private function formatPhoneCommand(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone) ?: $phone;
        $digits = $digits !== '' ? $digits : '+000000000';

        return sprintf('9-1M*%s', $digits);
    }

    private function buildSellCommand(array $segment, ?string $preferredClass, int $seatCount): string
    {
        $carrier = strtoupper((string) ($segment['marketing_carrier'] ?? $segment['owner'] ?? 'XX'));
        $flightNumber = preg_replace('/\D+/', '', (string) ($segment['marketing_flight_number'] ?? '0000')) ?: '0000';
        $flightNumber = str_pad($flightNumber, 4, '0', STR_PAD_LEFT);

        $bookingClass = strtoupper($preferredClass ?: $this->determineBookingClass($segment));

        $departure = Arr::get($segment, 'departure');
        $departureDate = $departure ? Carbon::parse($departure) : Carbon::now();
        $dateCode = strtoupper($departureDate->format('dM'));

        $origin = strtoupper((string) ($segment['origin'] ?? 'XXX'));
        $destination = strtoupper((string) ($segment['destination'] ?? 'XXX'));

        return sprintf(
            '0%s%s%s%s%sNN%d',
            $carrier,
            $flightNumber,
            $bookingClass,
            $dateCode,
            $origin . $destination,
            max(1, $seatCount)
        );
    }

    private function determineBookingClass(array $segment): string
    {
        $availability = Arr::get($segment, 'availability', []);

        if (is_array($availability)) {
            foreach ($availability as $class => $seats) {
                if ((int) $seats > 0 && is_string($class) && $class !== '') {
                    return strtoupper($class);
                }
            }
        }

        return 'Y';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $value): ?array
    {
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
