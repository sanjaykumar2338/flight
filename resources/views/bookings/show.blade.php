@php
    $status = $booking->status === 'payment_failed' ? 'failed' : $booking->status;
    $statusClasses = [
        'paid' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-red-100 text-red-800',
        'awaiting_payment' => 'bg-amber-100 text-amber-800',
        'pending' => 'bg-gray-100 text-gray-700',
    ];
    $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-700';
    $pricing = json_decode($booking->pricing_json ?? '[]', true) ?: [];
    $itinerary = json_decode($booking->itinerary_json ?? '[]', true) ?: [];
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

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-6">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Summary</h3>
                        <dl class="mt-4 space-y-2 text-sm text-gray-700">
                            <div>
                                <dt class="font-medium text-gray-500">Airline</dt>
                                <dd>{{ $booking->airline_code ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Total Amount</dt>
                                <dd>{{ $booking->currency }} {{ number_format((float) ($booking->amount_final ?? 0), 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Customer</dt>
                                <dd>{{ $booking->customer_name ?? '—' }} ({{ $booking->customer_email ?? '—' }})</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Paystack Reference</dt>
                                <dd>{{ $booking->payment_reference ?? $latestTransaction?->reference ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Paid At</dt>
                                <dd>{{ $booking->paid_at?->toDayDateTimeString() ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    @if ($status !== 'paid')
                        <div class="rounded-lg border border-indigo-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900">Complete Your Payment</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Submit your details to continue with Paystack.
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
                                <div class="flex items-center gap-3">
                                    <x-primary-button>{{ __('Pay with Paystack') }}</x-primary-button>
                                    <x-input-error :messages="$errors->get('checkout')" />
                                </div>
                            </form>
                        </div>
                    @endif

                    @if ($isDemo)
                        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-6 shadow-sm">
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
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Itinerary</h3>
                        <pre class="mt-4 max-h-80 overflow-y-auto rounded bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode($itinerary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Pricing Breakdown</h3>
                        <pre class="mt-4 max-h-80 overflow-y-auto rounded bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode($pricing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
                <div class="mt-4 overflow-x-auto">
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
                                <tr>
                                    <td class="px-4 py-2 text-indigo-700">{{ $transaction->reference }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($transaction->status) }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($transaction->mode) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">
                                        {{ $transaction->currency }} {{ number_format((float) ($transaction->amount ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $transaction->created_at?->toDayDateTimeString() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
