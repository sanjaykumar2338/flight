<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PricingRule extends Model
{
    use HasFactory;

    public const KIND_COMMISSION = 'COMMISSION';
    public const KIND_DISCOUNT = 'DISCOUNT';

    public const CALC_BASE_PRICE = 'BASE_PRICE';
    public const CALC_TOTAL_PRICE = 'TOTAL_PRICE';

    public const USAGE_COMMISSION_BASE = 'commission_base';
    public const USAGE_DISCOUNT_BASE = 'discount_base';
    public const USAGE_DISCOUNT_TOTAL_PROMO = 'discount_total_promo';
    public const USAGE_COMMISSION_DISCOUNT_BASE = 'commission_discount_base';

    public const BOOKING_CLASS_USAGE_AT_LEAST_ONE = 'at_least_one';
    public const BOOKING_CLASS_USAGE_ONLY_LISTED = 'only_listed';
    public const BOOKING_CLASS_USAGE_EXCLUDE_LISTED = 'exclude_listed';

    public const AIRLINE_RULE_NO_RESTRICTION = 'no_restriction';
    public const AIRLINE_RULE_ONLY_LISTED = 'only';
    public const AIRLINE_RULE_EXCLUDE_LISTED = 'exclude';
    public const AIRLINE_RULE_DIFFERENT_MARKETING = 'different_marketing';
    public const AIRLINE_RULE_PLATING_ONLY = 'plating_only';
    public const AIRLINE_RULE_OTHER_THAN_PLATING = 'other_than_plating';

    public const FLIGHT_RESTRICTION_NONE = 'no_restriction';
    public const FLIGHT_RESTRICTION_ONLY_LISTED = 'only_listed';
    public const FLIGHT_RESTRICTION_EXCLUDE_LISTED = 'exclude_listed';

    public const CACHE_KEY_PREFIX = 'pricing:rules:carrier:';
    public const CACHE_KEY_GENERIC = '__ALL__';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'priority',
        'carrier',
        'plating_carrier',
        'marketing_carriers_rule',
        'marketing_carriers',
        'operating_carriers_rule',
        'operating_carriers',
        'flight_restriction_type',
        'flight_numbers',
        'usage',
        'origin',
        'destination',
        'both_ways',
        'travel_type',
        'cabin_class',
        'booking_class_rbd',
        'booking_class_usage',
        'passenger_types',
        'sales_since',
        'sales_till',
        'departures_since',
        'departures_till',
        'returns_since',
        'returns_till',
        'fare_type',
        'promo_code',
        'kind',
        'calc_basis',
        'percent',
        'flat_amount',
        'fee_percent',
        'fixed_fee',
        'is_primary_pcc',
        'active',
        'notes',
    ];

    protected $casts = [
        'priority' => 'integer',
        'both_ways' => 'boolean',
        'percent' => 'decimal:4',
        'flat_amount' => 'decimal:2',
        'fee_percent' => 'decimal:4',
        'fixed_fee' => 'decimal:2',
        'active' => 'boolean',
        'is_primary_pcc' => 'boolean',
        'passenger_types' => 'array',
        'marketing_carriers' => 'array',
        'operating_carriers' => 'array',
        'sales_since' => 'datetime',
        'sales_till' => 'datetime',
        'departures_since' => 'datetime',
        'departures_till' => 'datetime',
        'returns_since' => 'datetime',
        'returns_till' => 'datetime',
    ];

    public static function carrierRuleOptions(): array
    {
        return [
            self::AIRLINE_RULE_NO_RESTRICTION,
            self::AIRLINE_RULE_ONLY_LISTED,
            self::AIRLINE_RULE_EXCLUDE_LISTED,
            self::AIRLINE_RULE_DIFFERENT_MARKETING,
            self::AIRLINE_RULE_PLATING_ONLY,
            self::AIRLINE_RULE_OTHER_THAN_PLATING,
            '', // UI code: Without restrictions
            'Y', // UI code: Different marketing carriers
            'N', // UI code: Plating carrier only
            'D', // UI code: Only other than plating carrier
        ];
    }

    public static function normalizeCarrierRule(?string $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return match ($normalized) {
            '', self::AIRLINE_RULE_NO_RESTRICTION => self::AIRLINE_RULE_NO_RESTRICTION,
            'Y', self::AIRLINE_RULE_DIFFERENT_MARKETING => self::AIRLINE_RULE_DIFFERENT_MARKETING,
            'N', self::AIRLINE_RULE_PLATING_ONLY => self::AIRLINE_RULE_PLATING_ONLY,
            'D', self::AIRLINE_RULE_OTHER_THAN_PLATING => self::AIRLINE_RULE_OTHER_THAN_PLATING,
            self::AIRLINE_RULE_ONLY_LISTED => self::AIRLINE_RULE_DIFFERENT_MARKETING, // legacy map
            self::AIRLINE_RULE_EXCLUDE_LISTED => self::AIRLINE_RULE_OTHER_THAN_PLATING, // legacy map
            default => null,
        };
    }

    public static function flightRestrictionOptions(): array
    {
        return [
            self::FLIGHT_RESTRICTION_NONE,
            self::FLIGHT_RESTRICTION_ONLY_LISTED,
            self::FLIGHT_RESTRICTION_EXCLUDE_LISTED,
        ];
    }

    public static function usageOptions(): array
    {
        return [
            self::USAGE_COMMISSION_BASE,
            self::USAGE_DISCOUNT_BASE,
            self::USAGE_DISCOUNT_TOTAL_PROMO,
            self::USAGE_COMMISSION_DISCOUNT_BASE,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeForCarrier(Builder $query, string $carrier): Builder
    {
        $carrier = strtoupper(trim($carrier));

        return $query->where(function (Builder $inner) use ($carrier) {
            $inner->whereNull('carrier')
                ->orWhere('carrier', $carrier);
        });
    }

    public function scopeForOrigin(Builder $query, ?string $origin): Builder
    {
        if (!$origin) {
            return $query;
        }

        $origin = strtoupper(trim($origin));

        return $query->where(function (Builder $inner) use ($origin) {
            $inner->whereNull('origin')
                ->orWhere('origin', $origin);
        });
    }

    public function scopeForDestination(Builder $query, ?string $destination): Builder
    {
        if (!$destination) {
            return $query;
        }

        $destination = strtoupper(trim($destination));

        return $query->where(function (Builder $inner) use ($destination) {
            $inner->whereNull('destination')
                ->orWhere('destination', $destination);
        });
    }

    public function scopeForTravelDirection(Builder $query, bool $bothWays): Builder
    {
        if ($bothWays) {
            return $query->where(function (Builder $inner) {
                $inner->whereNull('both_ways')
                    ->orWhere('both_ways', true);
            });
        }

        return $query->where(function (Builder $inner) {
            $inner->whereNull('both_ways')
                ->orWhere('both_ways', false);
        });
    }

    public function scopeForTravelType(Builder $query, ?string $travelType): Builder
    {
        if (!$travelType) {
            return $query;
        }

        $travelType = strtoupper(trim($travelType));

        return $query->where(function (Builder $inner) use ($travelType) {
            $inner->whereNull('travel_type')
                ->orWhere('travel_type', $travelType)
                ->orWhere('travel_type', 'OW+RT');
        });
    }

    public function scopeForCabin(Builder $query, ?string $cabin): Builder
    {
        if (!$cabin) {
            return $query;
        }

        $cabin = trim($cabin);

        return $query->where(function (Builder $inner) use ($cabin) {
            $inner->whereNull('cabin_class')
                ->orWhere('cabin_class', $cabin);
        });
    }

    public function scopeForBookingClass(Builder $query, ?string $bookingClass): Builder
    {
        if (!$bookingClass) {
            return $query->where(function (Builder $inner) {
                $inner->whereNull('booking_class_rbd')
                    ->orWhereNull('booking_class_usage');
            });
        }

        $bookingClass = strtoupper(trim($bookingClass));

        return $query->where(function (Builder $inner) use ($bookingClass) {
            $inner->whereNull('booking_class_rbd')
                ->orWhere(function (Builder $nested) use ($bookingClass) {
                    $nested->where('booking_class_usage', self::BOOKING_CLASS_USAGE_AT_LEAST_ONE)
                        ->where('booking_class_rbd', $bookingClass);
                })
                ->orWhere(function (Builder $nested) use ($bookingClass) {
                    $nested->where('booking_class_usage', self::BOOKING_CLASS_USAGE_ONLY_LISTED)
                        ->where('booking_class_rbd', $bookingClass);
                })
                ->orWhere(function (Builder $nested) use ($bookingClass) {
                    $nested->where('booking_class_usage', self::BOOKING_CLASS_USAGE_EXCLUDE_LISTED)
                        ->where('booking_class_rbd', '!=', $bookingClass);
                });
        });
    }

    public function scopeForFareType(Builder $query, ?string $fareType): Builder
    {
        if (!$fareType) {
            return $query;
        }

        $fareType = strtolower(trim($fareType));

        return $query->where(function (Builder $inner) use ($fareType) {
            $inner->whereNull('fare_type')
                ->orWhere('fare_type', $fareType)
                ->orWhere('fare_type', 'public_and_private');
        });
    }

    public function scopeWithPromo(Builder $query, ?string $promo): Builder
    {
        if (!$promo) {
            return $query->whereNull('promo_code');
        }

        $promo = strtoupper(trim($promo));

        return $query->where(function (Builder $inner) use ($promo) {
            $inner->whereNull('promo_code')
                ->orWhere('promo_code', $promo);
        });
    }

    public function scopeForPassengerTypes(Builder $query, array $passengerTypes): Builder
    {
        $filtered = collect($passengerTypes)
            ->filter()
            ->map(fn ($type) => strtoupper(trim((string) $type)))
            ->values();

        if ($filtered->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($filtered) {
            $inner->whereNull('passenger_types')
                ->orWhereJsonContains('passenger_types', $filtered->all());
        });
    }

    public function isCommission(): bool
    {
        return $this->kind === self::KIND_COMMISSION;
    }

    public function isDiscount(): bool
    {
        return $this->kind === self::KIND_DISCOUNT;
    }

    protected static function booted(): void
    {
        $flush = function (PricingRule $rule): void {
            $rule->flushCarrierCache();
        };

        static::saved($flush);
        static::deleted($flush);
    }

    public function flushCarrierCache(): void
    {
        $carriers = [];

        if ($this->carrier) {
            $carriers[] = strtoupper($this->carrier);
        }

        $carriers[] = self::CACHE_KEY_GENERIC;

        foreach (array_unique($carriers) as $carrier) {
            Cache::forget(self::CACHE_KEY_PREFIX.$carrier);
        }
    }

    public function setCarrierAttribute(?string $value): void
    {
        $this->attributes['carrier'] = $value ? strtoupper(trim($value)) : null;
    }

    public function setUsageAttribute(?string $value): void
    {
        $usage = $value ? strtolower(trim($value)) : null;
        $this->attributes['usage'] = $usage;
        $this->attributes['kind'] = $this->deriveKindFromUsage($usage);
    }

    public function setPromoCodeAttribute(?string $value): void
    {
        $this->attributes['promo_code'] = $value ? strtoupper(trim($value)) : null;
    }

    protected function deriveKindFromUsage(?string $usage): string
    {
        return match ($usage) {
            self::USAGE_DISCOUNT_BASE,
            self::USAGE_DISCOUNT_TOTAL_PROMO => self::KIND_DISCOUNT,
            default => self::KIND_COMMISSION,
        };
    }
}
