<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('My Bookings') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Booking</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Airline</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Created</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($bookings as $booking)
                                @php
                                    $status = $booking->status === 'payment_failed' ? 'failed' : $booking->status;
                                    $statusClass = match ($status) {
                                        'paid' => 'bg-emerald-100 text-emerald-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'awaiting_payment' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-gray-900">#{{ $booking->id }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $booking->airline_code ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                        {{ $booking->currency }} {{ number_format($booking->amount_final, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $booking->created_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('bookings.show', $booking) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                        You do not have any bookings yet.
                                    </td>
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
