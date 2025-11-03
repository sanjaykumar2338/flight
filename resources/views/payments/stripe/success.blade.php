<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Payment Confirmation') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-emerald-900">Thanks! Your payment is processing.</h3>
                <p class="mt-2 text-sm text-emerald-800">
                    If the payment was successful, your booking will be marked as paid shortly. You can return to the booking dashboard to review the latest status.
                </p>
                @if ($sessionId)
                    <p class="mt-3 text-xs text-emerald-700">
                        Stripe reference: <span class="font-semibold">{{ $sessionId }}</span>
                    </p>
                @endif
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('bookings.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                    {{ __('Back to My Bookings') }}
                </a>
                <a href="{{ route('flights.search') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-700">
                    {{ __('Search Flights') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
