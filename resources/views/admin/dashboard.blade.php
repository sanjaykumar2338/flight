<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

@php
    $usageOptions = [
        \App\Models\PricingRule::USAGE_COMMISSION_BASE => 'Commission from base price',
        \App\Models\PricingRule::USAGE_DISCOUNT_BASE => 'Discount from base price',
        \App\Models\PricingRule::USAGE_DISCOUNT_TOTAL_PROMO => 'Discount from total price & promo code',
        \App\Models\PricingRule::USAGE_COMMISSION_DISCOUNT_BASE => 'Commission with discount from base price',
    ];
    $travelTypes = ['OW' => 'One Way', 'RT' => 'Round Trip', 'OW+RT' => 'One Way & Round Trip'];
@endphp

<div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Pricing Rules Active</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['active_rules'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Commissions &amp; Discounts</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">C: {{ $metrics['commission_rules'] }} / D: {{ $metrics['discount_rules'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Carriers With Rules</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['carriers_with_rules'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Registered Users</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['users_total'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Bookings (coming soon)</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['bookings_total'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Payments Confirmed (coming soon)</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['payments_total'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Referral Clicks (coming soon)</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['referral_clicks'] }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Recent Pricing Rule Updates</h3>
                <p class="mt-1 text-sm text-gray-500">Latest adjustments across carriers and scopes.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Carrier</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Scope</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Usage</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Active</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($recentRules as $rule)
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-900">{{ $rule->carrier }}</div>
                                        <div class="text-xs text-gray-500">Priority {{ $rule->priority }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        <div>{{ $rule->origin ?? 'ANY' }} → {{ $rule->destination ?? 'ANY' }} {{ $rule->both_ways ? '(Both ways)' : '' }}</div>
                                        <div class="text-xs text-gray-500">{{ $travelTypes[$rule->travel_type] ?? $rule->travel_type ?? '—' }} · {{ $rule->cabin_class ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        <div>{{ $usageOptions[$rule->usage] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $rule->usage ?? '')) }}</div>
                                        <div class="text-xs text-gray-500">
                                            @php
                                                $pieces = [];
                                                if (!is_null($rule->percent)) {
                                                    $pieces[] = number_format((float) $rule->percent, 2).'%' ;
                                                }
                                                if (!is_null($rule->flat_amount)) {
                                                    $pieces[] = number_format((float) $rule->flat_amount, 2);
                                                }
                                                if (!is_null($rule->fee_percent)) {
                                                    $pieces[] = number_format((float) $rule->fee_percent, 2).'%';
                                                }
                                                if (!is_null($rule->fixed_fee)) {
                                                    $pieces[] = number_format((float) $rule->fixed_fee, 2);
                                                }
                                            @endphp
                                            {{ implode(' · ', $pieces) ?: '—' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $rule->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                            {{ $rule->active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $rule->updated_at?->diffForHumans() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No pricing rules yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col gap-2 text-right md:flex-row md:items-center md:justify-between">
                    <a href="{{ route('admin.pricing.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                        Manage Pricing Rules →
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
