<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Bookings') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach (['pending', 'awaiting_payment', 'paid', 'failed'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter_airline" value="Airline" />
                        <x-text-input id="filter_airline" name="airline" type="text" maxlength="3" class="mt-1 block w-full uppercase"
                            value="{{ $filters['airline'] ?? '' }}" />
                    </div>
                    <div>
                        <x-input-label for="filter_ref" value="Referral Code" />
                        <x-text-input id="filter_ref" name="ref" type="text" class="mt-1 block w-full"
                            value="{{ $filters['ref'] ?? '' }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="filter_date_from" value="Date From" />
                            <x-text-input id="filter_date_from" name="date_from" type="date" class="mt-1 block w-full"
                                value="{{ $filters['date_from'] ?? '' }}" />
                        </div>
                        <div>
                            <x-input-label for="filter_date_to" value="Date To" />
                            <x-text-input id="filter_date_to" name="date_to" type="date" class="mt-1 block w-full"
                                value="{{ $filters['date_to'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reset</a>
                    </div>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Booking</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Airline</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Customer</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Created</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">#{{ $booking->id }}</div>
                                        <div class="text-xs text-gray-500">{{ $booking->priced_offer_ref ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-800">{{ $booking->airline_code ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="font-medium text-gray-900">{{ $booking->customer_name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $booking->customer_email ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                        {{ $booking->currency }} {{ number_format($booking->amount_final, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $status = $booking->status === 'payment_failed' ? 'failed' : $booking->status;
                                            $statusClass = match ($status) {
                                                'paid' => 'bg-emerald-100 text-emerald-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                'awaiting_payment' => 'bg-amber-100 text-amber-800',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $booking->created_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No bookings found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $bookings->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
