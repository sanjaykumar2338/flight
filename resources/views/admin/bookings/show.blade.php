<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Booking Details') }}
            </h2>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                ← Back to Bookings
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Summary</h3>
                    <dl class="mt-4 space-y-2 text-sm text-gray-700">
                        <div>
                            <dt class="font-medium text-gray-500">Booking ID</dt>
                            <dd>#{{ $booking->id }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Status</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', $booking->status === 'payment_failed' ? 'failed' : $booking->status)) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Airline</dt>
                            <dd>{{ $booking->airline_code ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Referral</dt>
                            <dd>{{ $booking->referral_code ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Customer</dt>
                            <dd>{{ $booking->customer_name ?? '—' }} ({{ $booking->customer_email ?? '—' }})</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Amount</dt>
                            <dd>{{ $booking->currency }} {{ number_format($booking->amount_final, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Payment Reference</dt>
                            <dd>{{ $booking->payment_reference ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Paid At</dt>
                            <dd>{{ optional($booking->paid_at)->toDayDateTimeString() ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm md:row-span-2">
                    <h3 class="text-lg font-semibold text-gray-900">Itinerary & Pricing</h3>
                    <div class="mt-3 space-y-3 text-sm text-gray-700">
                        <div>
                            <p class="font-medium text-gray-500">Segments</p>
                            <pre class="mt-1 overflow-x-auto rounded bg-gray-50 p-3 text-xs">{{ json_encode(json_decode($booking->itinerary_json, true), JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        <div>
                            <p class="font-medium text-gray-500">Pricing</p>
                            <pre class="mt-1 overflow-x-auto rounded bg-gray-50 p-3 text-xs">{{ json_encode(json_decode($booking->pricing_json, true), JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($timeline))
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Timeline</h3>
                    <ol class="mt-4 space-y-4">
                        @foreach ($timeline as $event)
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $event['label'] }}</p>
                                    <p class="text-xs text-gray-500">{{ optional($event['timestamp'])->toDayDateTimeString() }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $event['description'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
                    @if ($isDemo)
                        <a href="{{ route('bookings.demo', $booking) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                            Demo: Simulate Payment
                        </a>
                    @endif
                </div>

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
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.payments.show', $transaction) }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                                            {{ $transaction->reference }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">{{ ucfirst($transaction->status) }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($transaction->mode) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $transaction->created_at?->toDayDateTimeString() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
