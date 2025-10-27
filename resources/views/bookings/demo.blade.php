<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Demo Paystack Checkout') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-6 text-blue-900">
                <p class="font-semibold">Demo mode is active.</p>
                <p class="mt-1 text-sm">Use the buttons below to simulate a Paystack webhook response for booking #{{ $booking->id }}.</p>
            </div>

            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-red-800">
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Booking Details</h3>
                        <dl class="mt-3 space-y-2 text-sm text-gray-700">
                            <div>
                                <dt class="font-medium text-gray-500">Status</dt>
                                <dd>{{ ucfirst($booking->status) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Payable Total</dt>
                                <dd>{{ $booking->currency }} {{ number_format($booking->amount_final, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">Payment Reference</dt>
                                <dd>{{ $booking->payment_reference ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Latest Transaction</h3>
                        @if ($transaction)
                            <dl class="mt-3 space-y-2 text-sm text-gray-700">
                                <div>
                                    <dt class="font-medium text-gray-500">Reference</dt>
                                    <dd>{{ $transaction->reference }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Status</dt>
                                    <dd>{{ ucfirst($transaction->status) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Amount</dt>
                                    <dd>{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</dd>
                                </div>
                            </dl>
                        @else
                            <p class="mt-3 text-sm text-gray-500">No transactions yet.</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('bookings.demo.simulate', [$booking, 'success']) }}">
                        @csrf
                        <x-primary-button>{{ __('Simulate Success') }}</x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('bookings.demo.simulate', [$booking, 'failed']) }}">
                        @csrf
                        <x-danger-button>{{ __('Simulate Failure') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
