<?php

namespace App\Http\Requests;

use App\Models\PricingRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'carrier' => ['nullable', 'string', 'max:5'],
            'plating_carrier' => ['nullable', 'string', 'max:5'],
            'marketing_carriers_rule' => ['nullable', Rule::in(PricingRule::carrierRuleOptions())],
            'marketing_carriers' => ['nullable'],
            'marketing_carriers.*' => ['string', 'max:5'],
            'operating_carriers_rule' => ['nullable', Rule::in(PricingRule::carrierRuleOptions())],
            'operating_carriers' => ['nullable'],
            'operating_carriers.*' => ['string', 'max:5'],
            'flight_restriction_type' => ['nullable', Rule::in(PricingRule::flightRestrictionOptions())],
            'flight_numbers' => ['nullable', 'string'],
            'usage' => ['required', Rule::in(PricingRule::usageOptions())],
            'origin' => ['nullable', 'string', 'size:3'],
            'destination' => ['nullable', 'string', 'size:3'],
            'both_ways' => ['sometimes', 'boolean'],
            'travel_type' => ['nullable', Rule::in(['OW', 'RT', 'OW+RT'])],
            'cabin_class' => ['nullable', Rule::in(['Economy', 'Premium Economy', 'Business', 'First', 'Premium First'])],
            'booking_class_rbd' => ['nullable', 'string', 'max:20'],
            'booking_class_usage' => ['nullable', Rule::in([
                PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE,
                PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED,
                PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED,
            ])],
            'passenger_types' => ['nullable', 'array'],
            'passenger_types.*' => ['string', 'max:10'],
            'sales_since' => ['nullable', 'date'],
            'sales_till' => ['nullable', 'date', 'after_or_equal:sales_since'],
            'departures_since' => ['nullable', 'date'],
            'departures_till' => ['nullable', 'date', 'after_or_equal:departures_since'],
            'returns_since' => ['nullable', 'date'],
            'returns_till' => ['nullable', 'date', 'after_or_equal:returns_since'],
            'fare_type' => ['nullable', Rule::in(['public', 'private', 'public_and_private'])],
            'promo_code' => ['nullable', 'string', 'max:32'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'flat_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'is_primary_pcc' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'return_url' => ['nullable', 'url'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $usage = $this->input('usage');

                if (in_array($usage, [
                    PricingRule::USAGE_COMMISSION_BASE,
                    PricingRule::USAGE_DISCOUNT_BASE,
                    PricingRule::USAGE_DISCOUNT_TOTAL_PROMO,
                ], true) && $this->missingPercentAndFlat()) {
                    $validator->addFailure('percent', 'required_without_all', ['values' => 'flat_amount']);
                    $validator->addFailure('flat_amount', 'required_without_all', ['values' => 'percent']);
                }

                if ($usage === PricingRule::USAGE_COMMISSION_DISCOUNT_BASE) {
                    if ($this->missingPercentAndFlat()) {
                        $validator->addFailure('percent', 'required_without_all', ['values' => 'flat_amount']);
                        $validator->addFailure('flat_amount', 'required_without_all', ['values' => 'percent']);
                    }

                    if ($this->missingFeeComponents()) {
                        $validator->addFailure('fee_percent', 'required_without_all', ['values' => 'fixed_fee']);
                        $validator->addFailure('fixed_fee', 'required_without_all', ['values' => 'fee_percent']);
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'priority' => $this->normalizeInteger($this->input('priority', 0)),
            'carrier' => $this->normalizeCarrier($this->input('carrier')),
            'plating_carrier' => $this->normalizeCarrier($this->input('plating_carrier')),
            'marketing_carriers_rule' => PricingRule::normalizeMarketingRule($this->input('marketing_carriers_rule')) ?? PricingRule::AIRLINE_RULE_NO_RESTRICTION,
            'marketing_carriers' => $this->normalizeCarriersList($this->input('marketing_carriers')),
            'operating_carriers_rule' => PricingRule::normalizeOperatingRule($this->input('operating_carriers_rule')) ?? PricingRule::AIRLINE_RULE_NO_RESTRICTION,
            'operating_carriers' => $this->normalizeCarriersList($this->input('operating_carriers')),
            'flight_restriction_type' => $this->normalizeEnum(
                $this->input('flight_restriction_type'),
                PricingRule::flightRestrictionOptions(),
                PricingRule::FLIGHT_RESTRICTION_NONE
            ),
            'flight_numbers' => $this->normalizeFlightNumbers($this->input('flight_numbers')),
            'origin' => $this->nullIfWildcard($this->normalizeIata($this->input('origin'))),
            'destination' => $this->nullIfWildcard($this->normalizeIata($this->input('destination'))),
            'travel_type' => $this->normalizeEnum($this->input('travel_type'), ['OW', 'RT', 'OW+RT'], null),
            'cabin_class' => $this->normalizeEnum($this->input('cabin_class'), ['Economy', 'Premium Economy', 'Business', 'First', 'Premium First'], null),
            'booking_class_rbd' => $this->nullIfWildcard($this->normalizeString($this->input('booking_class_rbd'))),
            'booking_class_usage' => $this->normalizeEnum(
                $this->input('booking_class_usage'),
                [
                    PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE,
                    PricingRule::BOOKING_CLASS_USAGE_ONLY_LISTED,
                    PricingRule::BOOKING_CLASS_USAGE_EXCLUDE_LISTED,
                ],
                null
            ),
            'fare_type' => $this->normalizeEnum($this->input('fare_type'), ['public', 'private', 'public_and_private'], null),
            'promo_code' => $this->nullIfBlank($this->normalizeString($this->input('promo_code'))),
            'usage' => $this->normalizeEnum($this->input('usage'), PricingRule::usageOptions(), PricingRule::USAGE_COMMISSION_BASE),
            'percent' => $this->normalizeDecimal($this->input('percent')),
            'flat_amount' => $this->normalizeDecimal($this->input('flat_amount'), 2),
            'fee_percent' => $this->normalizeDecimal($this->input('fee_percent')),
            'fixed_fee' => $this->normalizeDecimal($this->input('fixed_fee'), 2),
            'both_ways' => $this->boolean('both_ways'),
            'is_primary_pcc' => $this->boolean('is_primary_pcc'),
            'active' => $this->boolean('active'),
            'passenger_types' => $this->normalizePassengerTypes($this->input('passenger_types', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $validated = \Illuminate\Support\Arr::except($this->validated(), ['return_url']);

        return array_merge($validated, [
            'priority' => $this->validated('priority') ?? 0,
            'plating_carrier' => $this->validated('plating_carrier'),
            'marketing_carriers_rule' => $this->validated('marketing_carriers_rule'),
            'marketing_carriers' => $this->validated('marketing_carriers'),
            'operating_carriers_rule' => $this->validated('operating_carriers_rule'),
            'operating_carriers' => $this->validated('operating_carriers'),
            'flight_restriction_type' => $this->validated('flight_restriction_type'),
            'flight_numbers' => $this->validated('flight_numbers'),
            'passenger_types' => empty($this->validated('passenger_types')) ? null : $this->validated('passenger_types'),
            'percent' => $this->validated('percent'),
            'flat_amount' => $this->validated('flat_amount'),
            'fee_percent' => $this->validated('fee_percent'),
            'fixed_fee' => $this->validated('fixed_fee'),
            'booking_class_rbd' => $this->validated('booking_class_rbd'),
            'booking_class_usage' => $this->validated('booking_class_usage'),
            'promo_code' => $this->validated('promo_code'),
            'notes' => $this->validated('notes'),
            'both_ways' => (bool) $this->validated('both_ways'),
            'is_primary_pcc' => (bool) $this->validated('is_primary_pcc'),
            'active' => (bool) $this->validated('active'),
            'calc_basis' => $this->resolveCalcBasis($this->validated('usage')),
        ]);
    }

    private function missingPercentAndFlat(): bool
    {
        $percentRaw = $this->input('percent');
        $flatRaw = $this->input('flat_amount');

        return ($percentRaw === null || $percentRaw === '') && ($flatRaw === null || $flatRaw === '');
    }

    private function missingFeeComponents(): bool
    {
        $feePercentRaw = $this->input('fee_percent');
        $fixedFeeRaw = $this->input('fixed_fee');

        return ($feePercentRaw === null || $feePercentRaw === '') && ($fixedFeeRaw === null || $fixedFeeRaw === '');
    }

    private function normalizeInteger(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function normalizeDecimal(mixed $value, int $precision = 4): ?float
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $rounded = round((float) $value, $precision);

        return $rounded;
    }

    private function normalizeString(mixed $value): ?string
    {
        $value = is_string($value) ? strtoupper(trim($value)) : null;

        return $value === '' ? null : $value;
    }

    private function normalizeIata(mixed $value): ?string
    {
        $normalized = $this->normalizeString($value);

        return $normalized ? substr($normalized, 0, 3) : null;
    }

    private function normalizeCarrier(mixed $value): ?string
    {
        $normalized = $this->normalizeString($value);

        return $normalized ? substr($normalized, 0, 5) : null;
    }

    private function nullIfWildcard(?string $value): ?string
    {
        return $value === null || strtoupper($value) === 'ANY' ? null : $value;
    }

    private function nullIfBlank(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }

    private function normalizeEnum(mixed $value, array $options, ?string $default): ?string
    {
        $normalized = $this->normalizeString($value);

        if ($normalized === null) {
            return $default;
        }

        foreach ($options as $option) {
            if (strcasecmp($option, $normalized) === 0) {
                return $option;
            }
        }

        return $default;
    }

    /**
     * @param mixed $types
     * @return array<int, string>
     */
    private function normalizePassengerTypes(mixed $types): array
    {
        if (!is_array($types)) {
            return [];
        }

        return collect($types)
            ->filter()
            ->map(fn ($type) => substr($this->normalizeString($type) ?? '', 0, 10))
            ->reject(fn ($type) => $type === '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeFlightNumbers(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if (!is_string($value)) {
            return null;
        }

        $numbers = collect(explode(',', $value))
            ->map(fn ($number) => strtoupper(trim($number)))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return null;
        }

        return $numbers->implode(',');
    }

    private function normalizeCarriersList(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if (!is_string($value)) {
            return null;
        }

        $codes = collect(explode(',', $value))
            ->map(fn ($code) => $this->normalizeCarrier($code))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return null;
        }

        return $codes->all();
    }

    private function resolveCalcBasis(?string $usage): string
    {
        return match ($usage) {
            PricingRule::USAGE_DISCOUNT_TOTAL_PROMO => PricingRule::CALC_TOTAL_PRICE,
            default => PricingRule::CALC_BASE_PRICE,
        };
    }

}
