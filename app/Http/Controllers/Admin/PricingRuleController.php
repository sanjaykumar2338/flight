<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pricing\ImportLegacyCommissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\PricingRuleRequest;
use App\Models\PricingRule;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PricingRuleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        $query = PricingRule::query();
        $this->applyFilters($query, $filters);

        $rules = $query
            ->orderBy('priority')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => PricingRule::count(),
            'active' => PricingRule::where('active', true)->count(),
            'inactive' => PricingRule::where('active', false)->count(),
        ];

        $seededCarriers = DB::table('pricing_dropdown_options')
            ->where('type', 'carriers')
            ->orderBy('sort_order')
            ->pluck('value')
            ->filter()
            ->values();

        $existingCarriers = PricingRule::query()
            ->whereNotNull('carrier')
            ->distinct()
            ->pluck('carrier');

        $options = [
            'carriers' => $seededCarriers->merge($existingCarriers)->filter()->unique()->sort()->values()->all(),
            'passenger_types' => PricingRule::query()
                ->whereNotNull('passenger_types')
                ->pluck('passenger_types')
                ->flatten()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            // Keep usage creation simple: only commission rules can be created from the UI.
            'usage_options' => [
                'commission_base' => 'Commission from base price',
                'commission_discount_base' => 'Commission with discount from base price',
                'discount_base' => 'Discount from base price',
                'discount_total_promo' => 'Discount from total price & promo code',
            ],
            'creation_usage_options' => [
                'commission_base' => 'Commission from base price',
            ],
            'travel_types' => DB::table('pricing_dropdown_options')->where('type', 'travel_types')->orderBy('sort_order')->pluck('label', 'value')->toArray(),
            'fare_types' => DB::table('pricing_dropdown_options')->where('type', 'fare_types')->orderBy('sort_order')->pluck('label', 'value')->toArray(),
            'cabin_classes' => DB::table('pricing_dropdown_options')->where('type', 'cabin_classes')->orderBy('sort_order')->pluck('label', 'value')->toArray(),
        ];

        $commissionOverview = PricingRule::query()
            ->active()
            ->whereIn('usage', [
                PricingRule::USAGE_COMMISSION_BASE,
                PricingRule::USAGE_COMMISSION_DISCOUNT_BASE,
            ])
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PricingRule $rule) => $rule->carrier ?: '__ALL__')
            ->map(fn ($group) => $group->first())
            ->values();

        return view('admin.pricing.index', [
            'rules' => $rules,
            'filters' => $filters,
            'stats' => $stats,
            'options' => $options,
            'featureEnabled' => config('pricing.rules.enabled'),
            'tab' => $request->input('tab', 'rules'),
            'commissionOverview' => $commissionOverview,
        ]);
    }

    public function store(PricingRuleRequest $request): RedirectResponse
    {
        $rule = PricingRule::create($request->data());

        return redirect()
            ->to($request->input('return_url') ?: route('admin.pricing.index'))
            ->with('status', "Pricing rule #{$rule->id} created.");
    }

    public function update(PricingRuleRequest $request, PricingRule $pricingRule): RedirectResponse
    {
        $pricingRule->update($request->data());

        return redirect()
            ->to($request->input('return_url') ?: route('admin.pricing.index'))
            ->with('status', "Pricing rule #{$pricingRule->id} updated.");
    }

    public function destroy(Request $request, PricingRule $pricingRule): RedirectResponse
    {
        $pricingRule->delete();

        $redirectTo = $request->input('return_url');

        if ($redirectTo && !filter_var($redirectTo, FILTER_VALIDATE_URL)) {
            $redirectTo = null;
        }

        return redirect()
            ->to($redirectTo ?: route('admin.pricing.index'))
            ->with('status', "Pricing rule #{$pricingRule->id} deleted.");
    }

    public function importLegacy(ImportLegacyCommissions $importer): RedirectResponse
    {
        $count = $importer->handle();

        return redirect()
            ->route('admin.pricing.index')
            ->with('status', $count > 0 ? "Imported {$count} legacy commissions." : 'No legacy commissions needed importing.');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        $toNull = fn ($value) => ($value === null || $value === '') ? null : $value;

        return [
            'carrier' => strtoupper((string) $request->input('carrier', '')),
            'usage' => $toNull(strtolower((string) $request->input('usage'))),
            'origin' => strtoupper((string) $request->input('origin', '')),
            'destination' => strtoupper((string) $request->input('destination', '')),
            'both_ways' => $toNull($request->input('both_ways')) === null
                ? null
                : ($request->input('both_ways') === '1'),
            'travel_type' => $toNull($request->input('travel_type')),
            'cabin_class' => $toNull($request->input('cabin_class')),
            'booking_class_rbd' => strtoupper((string) $request->input('booking_class_rbd', '')),
            'booking_class_usage' => $toNull($request->input('booking_class_usage')),
            'passenger_types' => collect(Arr::wrap($request->input('passenger_types', [])))
                ->filter()
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->unique()
                ->values()
                ->all(),
            'sales_range' => [
                'since' => $request->input('sales_since'),
                'till' => $request->input('sales_till'),
            ],
            'departures_range' => [
                'since' => $request->input('departures_since'),
                'till' => $request->input('departures_till'),
            ],
            'returns_range' => [
                'since' => $request->input('returns_since'),
                'till' => $request->input('returns_till'),
            ],
            'fare_type' => $toNull($request->input('fare_type')),
            'promo_code' => strtoupper((string) $request->input('promo_code', '')),
            'active' => $toNull($request->input('active')) === null
                ? null
                : ($request->input('active') === '1'),
        ];
    }

    /**
     * @param Builder<PricingRule> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['carrier']) {
            $query->where('carrier', $filters['carrier']);
        }

        if ($filters['usage']) {
            $query->where('usage', $filters['usage']);
        }

        if ($filters['origin']) {
            $query->where(function (Builder $inner) use ($filters) {
                $inner->where('origin', $filters['origin'])
                    ->orWhere(function (Builder $nested) use ($filters) {
                        $nested->where('both_ways', true)->where('destination', $filters['origin']);
                    });
            });
        }

        if ($filters['destination']) {
            $query->where(function (Builder $inner) use ($filters) {
                $inner->where('destination', $filters['destination'])
                    ->orWhere(function (Builder $nested) use ($filters) {
                        $nested->where('both_ways', true)->where('origin', $filters['destination']);
                    });
            });
        }

        if ($filters['both_ways'] !== null) {
            $query->where('both_ways', $filters['both_ways']);
        }

        if ($filters['travel_type']) {
            $query->where('travel_type', $filters['travel_type']);
        }

        if ($filters['cabin_class']) {
            $query->where('cabin_class', $filters['cabin_class']);
        }

        if ($filters['booking_class_rbd']) {
            $query->where('booking_class_rbd', $filters['booking_class_rbd']);
        }

        if ($filters['booking_class_usage']) {
            $query->where('booking_class_usage', $filters['booking_class_usage']);
        }

        if (!empty($filters['passenger_types'])) {
            foreach ($filters['passenger_types'] as $type) {
                $query->whereJsonContains('passenger_types', strtoupper($type));
            }
        }

        if ($filters['fare_type']) {
            $query->where('fare_type', $filters['fare_type']);
        }

        if ($filters['promo_code']) {
            $query->where('promo_code', $filters['promo_code']);
        }

        if ($filters['active'] !== null) {
            $query->where('active', $filters['active']);
        }

        $this->applyDateRangeFilter($query, 'sales', $filters['sales_range']);
        $this->applyDateRangeFilter($query, 'departures', $filters['departures_range']);
        $this->applyDateRangeFilter($query, 'returns', $filters['returns_range']);
    }

    /**
     * @param Builder<PricingRule> $query
     * @param array{since: string|null, till: string|null} $range
     */
    private function applyDateRangeFilter(Builder $query, string $prefix, array $range): void
    {
        $since = $this->parseDate($range['since']);
        $till = $this->parseDate($range['till']);

        if (!$since && !$till) {
            return;
        }

        $sinceColumn = "{$prefix}_since";
        $tillColumn = "{$prefix}_till";

        if ($since) {
            $query->where(function (Builder $builder) use ($since, $sinceColumn) {
                $builder->whereNull($sinceColumn)->orWhereDate($sinceColumn, '<=', $since);
            });
        }

        if ($till) {
            $query->where(function (Builder $builder) use ($till, $tillColumn) {
                $builder->whereNull($tillColumn)->orWhereDate($tillColumn, '>=', $till);
            });
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
