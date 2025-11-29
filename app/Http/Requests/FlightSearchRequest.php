<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public const DEFAULT_FLEXIBLE_DAYS = 3;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (!$this->hasSearchCriteria()) {
            return [];
        }

        return [
            'origin' => ['required', 'string', 'size:3'],
            'destination' => ['required', 'string', 'size:3', 'different:origin'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after:departure_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:9'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:9'],
            'cabin_class' => ['required', 'string', 'in:ECONOMY,BUSINESS,PREMIUM_ECONOMY,FIRST'],
            'airlines' => ['sometimes', 'array'],
            'airlines.*' => ['string', 'size:2'],
            'interline' => ['nullable', 'string', 'in:Y,N,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'airlines.*.size' => 'Airline filters should be two-letter carrier codes.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('origin')) {
            $this->merge(['origin' => strtoupper(trim($this->input('origin')))]);
        }

        if ($this->has('destination')) {
            $this->merge(['destination' => strtoupper(trim($this->input('destination')))]);
        }

        if ($this->has('selected_airlines')) {
            $selected = $this->input('selected_airlines');
            $selectedValues = is_array($selected)
                ? $selected
                : explode(',', (string) $selected);

            $this->merge([
                'airlines' => collect($selectedValues)
                    ->map(fn ($code) => strtoupper(trim((string) $code)))
                    ->filter(fn ($code) => $code !== '')
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }

        if ($this->has('airlines') && is_array($this->input('airlines'))) {
            $this->merge([
                'airlines' => collect($this->input('airlines'))
                    ->filter()
                    ->map(fn ($code) => strtoupper(trim((string) $code)))
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }

        $this->merge([
            'adults' => $this->input('adults', 1),
            'children' => $this->input('children', 0),
            'infants' => $this->input('infants', 0),
            'cabin_class' => strtoupper($this->input('cabin_class', 'ECONOMY')),
            'interline' => $this->normalizeInterline($this->input('interline')),
        ]);
    }

    public function hasSearchCriteria(): bool
    {
        return $this->filled('origin') && $this->filled('destination') && $this->filled('departure_date');
    }

    public function flexibleDays(): int
    {
        return self::DEFAULT_FLEXIBLE_DAYS;
    }

    public function airlineFilters(): array
    {
        return $this->input('airlines', []);
    }

    private function normalizeInterline(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['Y', 'N', 'D'], true) ? $normalized : null;
    }
}
