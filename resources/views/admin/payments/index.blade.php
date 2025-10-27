<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Payments') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('admin.payments.index') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach (['init', 'pending', 'success', 'failed'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter_provider" value="Provider" />
                        <x-text-input id="filter_provider" name="provider" type="text" class="mt-1 block w-full"
                            value="{{ $filters['provider'] ?? 'paystack' }}" />
                    </div>
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
                    <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        <a href="{{ route('admin.payments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reset</a>
                    </div>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Reference</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Booking</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Mode</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Created</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="px-4 py-2">
                                        <span class="font-semibold text-gray-900">{{ $transaction->reference }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($transaction->booking)
                                            <a href="{{ route('admin.bookings.show', $transaction->booking) }}" class="text-indigo-600 hover:text-indigo-500">
                                                #{{ $transaction->booking->id }}
                                            </a>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ ucfirst($transaction->status) }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($transaction->mode) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $transaction->created_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('admin.payments.show', $transaction) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
