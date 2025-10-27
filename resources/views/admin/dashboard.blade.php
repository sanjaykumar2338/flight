<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Airlines With Commission Rules</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['airlines_with_commissions'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Active Commission Profiles</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $metrics['active_commissions'] }}</p>
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
                <h3 class="text-lg font-semibold text-gray-900">Recent Commission Updates</h3>
                <p class="mt-1 text-sm text-gray-500">Latest airline commission settings for quick review.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Airline</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Percent</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Flat</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Active</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($recentCommissions as $commission)
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-900">{{ $commission->airline_code }}</div>
                                        <div class="text-xs text-gray-500">{{ $commission->airline_name ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-2">{{ number_format($commission->markup_percent, 2) }}%</td>
                                    <td class="px-4 py-2">{{ number_format($commission->flat_markup, 2) }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $commission->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                            {{ $commission->is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $commission->updated_at?->diffForHumans() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No commission data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-right">
                    <a href="{{ route('admin.airline-commissions.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                        Manage Airline Commissions →
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
