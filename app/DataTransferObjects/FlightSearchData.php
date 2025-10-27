<?php

namespace App\DataTransferObjects;

use Carbon\Carbon;

class FlightSearchData
{
    public function __construct(
        public readonly string $origin,
        public readonly string $destination,
        public readonly Carbon $departureDate,
        public readonly ?Carbon $returnDate,
        public readonly int $adults,
        public readonly int $children,
        public readonly int $infants,
        public readonly string $cabinClass
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            origin: strtoupper($payload['origin']),
            destination: strtoupper($payload['destination']),
            departureDate: Carbon::parse($payload['departure_date'])->startOfDay(),
            returnDate: isset($payload['return_date']) && $payload['return_date']
                ? Carbon::parse($payload['return_date'])->startOfDay()
                : null,
            adults: (int) ($payload['adults'] ?? 1),
            children: (int) ($payload['children'] ?? 0),
            infants: (int) ($payload['infants'] ?? 0),
            cabinClass: strtoupper($payload['cabin_class'] ?? 'ECONOMY'),
        );
    }

    public function passengers(): array
    {
        return array_filter([
            ['type' => 'ADT', 'count' => $this->adults],
            ['type' => 'CHD', 'count' => $this->children],
            ['type' => 'INF', 'count' => $this->infants],
        ], fn ($entry) => $entry['count'] > 0);
    }

    public function totalPassengers(): int
    {
        return collect($this->passengers())->sum('count');
    }

    public function isRoundTrip(): bool
    {
        return $this->returnDate !== null;
    }

    public function withAdjustedDates(Carbon $departureDate, ?Carbon $returnDate = null): self
    {
        return new self(
            origin: $this->origin,
            destination: $this->destination,
            departureDate: $departureDate->startOfDay(),
            returnDate: $returnDate?->startOfDay(),
            adults: $this->adults,
            children: $this->children,
            infants: $this->infants,
            cabinClass: $this->cabinClass,
        );
    }
}
