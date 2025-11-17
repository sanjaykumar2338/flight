@php
    $status = $booking->status === 'payment_failed' ? 'failed' : $booking->status;
    $statusClasses = [
        'paid' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-red-100 text-red-800',
        'awaiting_payment' => 'bg-amber-100 text-amber-800',
        'pending' => 'bg-gray-100 text-gray-700',
    ];
    $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-700';
    $canCollectPayment = in_array($booking->status, ['pending', 'awaiting_payment', 'failed', 'payment_failed'], true);
    $pricing = json_decode($booking->pricing_json ?? '[]', true) ?: [];
    $itinerary = json_decode($booking->itinerary_json ?? '[]', true) ?: [];
    $displayCurrency = $latestTransaction?->currency ?? $booking->currency;
    $displayAmount = (float) ($latestTransaction?->amount ?? $booking->amount_final ?? 0);
    $segments = isset($itinerary['segments']) && is_array($itinerary['segments']) ? $itinerary['segments'] : [];
    $ndcPricing = $pricing['ndc'] ?? [];
    $baseFare = (float) ($ndcPricing['base_amount'] ?? ($pricing['base_amount'] ?? 0));
    $taxes = (float) ($ndcPricing['tax_amount'] ?? ($pricing['tax_amount'] ?? 0));
    $payableTotal = (float) ($pricing['payable_total'] ?? $displayAmount);
    $adjustments = round($payableTotal - ($baseFare + $taxes), 2);
    $rulesApplied = isset($pricing['rules_applied']) && is_array($pricing['rules_applied']) ? $pricing['rules_applied'] : [];
    $engineUsed = (bool) data_get($pricing, 'engine.used', false);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ __('Booking') }} #{{ $booking->id }}
                </h2>
                <p class="text-sm text-gray-500">Created {{ $booking->created_at?->toDayDateTimeString() ?? '—' }}</p>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $badgeClass }}">
                {{ ucfirst(str_replace('_', ' ', $status)) }}
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-sky-500 px-6 py-6 text-white">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-white/80">Booking Overview</p>
                            <h1 class="mt-1 text-3xl font-semibold">Booking #{{ $booking->id }}</h1>
                            <p class="text-sm text-white/80">Created {{ $booking->created_at?->toDayDateTimeString() ?? '—' }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-white/20 px-4 py-2 text-sm font-semibold uppercase tracking-wide text-white shadow-sm backdrop-blur">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                    </div>
                </div>
                <div class="grid gap-4 px-6 py-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Amount</p>
                        <p class="mt-2 text-xl font-semibold text-slate-900">
                            {{ $displayCurrency }} {{ number_format($displayAmount, 2) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Includes fares, taxes, and adjustments.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Airline</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $booking->airline_code ?? '—' }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Primary Carrier: {{ $booking->primary_carrier ?? $booking->airline_code ?? '—' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Passenger</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $booking->customer_name ?? '—' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ $booking->customer_email ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paid At</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $booking->paid_at?->toDayDateTimeString() ?? 'Pending' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500 break-all">
                            Reference: {{ $booking->payment_reference ?? $latestTransaction?->reference ?? '—' }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Itinerary</h3>
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                {{ count($segments) }} {{ \Illuminate\Support\Str::plural('segment', count($segments)) }}
                            </span>
                        </div>
                        <div class="mt-4 space-y-4">
                            @forelse ($segments as $segment)
                                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-slate-50 p-5 shadow-sm">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm uppercase tracking-wide text-slate-500">Route</p>
                                            <p class="text-lg font-semibold text-slate-900">
                                                {{ strtoupper($segment['origin'] ?? '---') }}
                                                <span class="mx-1 text-slate-400">→</span>
                                                {{ strtoupper($segment['destination'] ?? '---') }}
                                            </p>
                                        </div>
                                        <div class="flex flex-col text-right text-xs text-slate-500">
                                            <span>{{ $segment['marketing_carrier'] ?? $segment['operating_carrier'] ?? '—' }} {{ $segment['marketing_flight_number'] ?? '' }}</span>
                                            @if (!empty($segment['equipment']))
                                                <span>Equipment: {{ $segment['equipment'] }}</span>
                                            @endif
                                            @if (!empty($segment['duration']))
                                                <span>Duration: {{ $segment['duration'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 text-slate-400">🛫</span>
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Departure</p>
                                                <p class="font-semibold text-slate-800">
                                                    {{ isset($segment['departure']) ? \Carbon\Carbon::parse($segment['departure'])->format('d M Y · H:i') : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 text-slate-400">🛬</span>
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Arrival</p>
                                                <p class="font-semibold text-slate-800">
                                                    {{ isset($segment['arrival']) ? \Carbon\Carbon::parse($segment['arrival'])->format('d M Y · H:i') : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
                                    No itinerary details are available for this booking.
                                </p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Pricing Breakdown</h3>
                            <span class="text-xs font-semibold uppercase tracking-wide {{ $adjustments >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                {{ $adjustments >= 0 ? '+' : '' }}{{ number_format($adjustments, 2) }} adjustments
                            </span>
                        </div>
                        <dl class="mt-4 space-y-3 text-sm text-slate-700">
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="font-medium text-slate-500">Base Fare</dt>
                                <dd class="text-base font-semibold text-slate-900">{{ $displayCurrency }} {{ number_format($baseFare, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="font-medium text-slate-500">Taxes &amp; Fees</dt>
                                <dd class="text-base font-semibold text-slate-900">{{ $displayCurrency }} {{ number_format($taxes, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="font-medium text-slate-500">Adjustments</dt>
                                <dd class="text-base font-semibold {{ $adjustments >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $adjustments >= 0 ? '+' : '-' }}{{ $displayCurrency }} {{ number_format(abs($adjustments), 2) }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-indigo-50 px-4 py-3">
                                <dt class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Payable Total</dt>
                                <dd class="text-lg font-bold text-indigo-700">{{ $displayCurrency }} {{ number_format($payableTotal, 2) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pricing Engine</p>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $engineUsed ? 'Rules Applied' : 'Legacy Pricing' }}
                                </span>
                            </div>
                            <div class="space-y-2">
                                @forelse ($rulesApplied as $rule)
                                    <div class="rounded-2xl border border-slate-100 bg-white px-4 py-3 shadow-sm">
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $rule['label'] ?? ($rule['id'] ? 'Rule #'.$rule['id'] : 'Adjustment') }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ strtoupper($rule['kind'] ?? '—') }} · {{ strtoupper($rule['basis'] ?? '—') }}
                                        </p>
                                        <p class="mt-2 text-sm font-semibold {{ ($rule['impact_amount'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $rule['impact'] ?? (($rule['impact_amount'] ?? 0) >= 0 ? '+' : '') . number_format((float) ($rule['impact_amount'] ?? 0), 2) }}
                                        </p>
                                    </div>
                                @empty
                                    <p class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                                        No dynamic pricing rules were applied to this booking.
                                    </p>
                                @endforelse
                            </div>

                            <details class="mt-6 rounded-2xl border border-gray-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <summary class="cursor-pointer text-sm font-semibold text-slate-700">
                                    View raw pricing payload
                                </summary>
                                <div class="mt-3 space-y-4">
                                    <pre class="max-h-60 overflow-y-auto rounded bg-white p-4 text-xs text-slate-700">{{ json_encode($pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </details>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    @if ($canCollectPayment)
                        <section class="rounded-3xl border border-indigo-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900">Complete Your Payment</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Confirm your contact details and choose a payment method to finalize this booking.
                            </p>

                            <form method="POST" action="{{ route('checkout.paystack') }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                <div>
                                    <x-input-label for="pay_name" value="Contact Name" />
                                    <x-text-input id="pay_name" name="name" type="text" class="mt-1 block w-full"
                                        value="{{ old('name', $booking->customer_name ?? auth()->user()->name ?? '') }}" required />
                                </div>
                                <div>
                                    <x-input-label for="pay_email" value="Contact Email" />
                                    <x-text-input id="pay_email" name="email" type="email" class="mt-1 block w-full"
                                        value="{{ old('email', $booking->customer_email ?? auth()->user()->email ?? '') }}" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-primary-button>{{ __('Pay with Paystack') }}</x-primary-button>
                                    <button
                                        type="button"
                                        data-stripe-url="{{ route('payments.stripe.checkout', $booking) }}"
                                        class="rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        data-stripe-button
                                        data-loading-text="{{ __('Redirecting...') }}"
                                    >
                                        {{ __('Pay with Stripe') }}
                                    </button>
                                    <x-input-error :messages="$errors->get('checkout')" />
                                    <p data-stripe-error class="text-sm text-red-600"></p>
                                </div>
                            </form>
                        </section>
                    @else
                        <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-emerald-900">Payment Complete</h3>
                            <p class="mt-2 text-sm text-emerald-700">
                                This booking is fully paid. A receipt was sent to {{ $booking->customer_email ?? 'the primary contact email' }}.
                            </p>
                            <ul class="mt-4 space-y-2 text-sm text-emerald-800">
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-emerald-500">✔</span>
                                    <span>Payment reference <span class="font-semibold">{{ $booking->payment_reference ?? $latestTransaction?->reference ?? '—' }}</span></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-emerald-500">✔</span>
                                    <span>Recorded at {{ $booking->paid_at?->toDayDateTimeString() ?? '—' }}</span>
                                </li>
                            </ul>
                        </section>
                    @endif

                    @if ($isDemo)
                        <section class="rounded-3xl border border-dashed border-gray-300 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900">Sandbox Simulation</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                These actions are only available in demo mode to test webhook flows.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('bookings.demo.simulate', [$booking, 'success']) }}">
                                    @csrf
                                    <x-primary-button type="submit">Simulate Success</x-primary-button>
                                </form>
                                <form method="POST" action="{{ route('bookings.demo.simulate', [$booking, 'failed']) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                        Simulate Fail
                                    </button>
                                </form>
                            </div>
                        </section>
                    @endif

                    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Quick Facts</h3>
                        <dl class="mt-4 space-y-3 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Booking Status</dt>
                                <dd class="font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $status)) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Mode</dt>
                                <dd class="font-semibold text-slate-900">{{ ucfirst($latestTransaction->mode ?? 'test') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Last Updated</dt>
                                <dd class="font-semibold text-slate-900">{{ $booking->updated_at?->toDayDateTimeString() ?? '—' }}</dd>
                            </div>
                        </dl>

                        <details class="mt-4 rounded-2xl border border-gray-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <summary class="cursor-pointer text-sm font-semibold text-slate-700">View itinerary JSON</summary>
                            <pre class="mt-3 max-h-60 overflow-y-auto rounded bg-white p-4 text-xs text-slate-700">{{ json_encode($itinerary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    </section>
                </div>
            </div>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        {{ $transactions->count() }} {{ \Illuminate\Support\Str::plural('record', $transactions->count()) }}
                    </p>
                </div>
                <div class="mt-4 overflow-hidden rounded-2xl border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Reference</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Mode</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($transactions as $transaction)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 font-semibold text-indigo-700">{{ $transaction->reference }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($transaction->status) }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($transaction->mode) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">
                                        {{ $transaction->currency }} {{ number_format((float) ($transaction->amount ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $transaction->created_at?->toDayDateTimeString() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        @include('payments.partials.stripe-checkout-script')
    @endpush
</x-app-layout>
