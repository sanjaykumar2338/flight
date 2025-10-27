<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Payment Details') }}
            </h2>
            <a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                ← Back to Payments
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Summary</h3>
                <dl class="mt-4 space-y-2 text-sm text-gray-700">
                    <div>
                        <dt class="font-medium text-gray-500">Reference</dt>
                        <dd>{{ $payment->reference }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Status</dt>
                        <dd>{{ ucfirst($payment->status) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Provider</dt>
                        <dd>{{ ucfirst($payment->provider) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Mode</dt>
                        <dd>{{ ucfirst($payment->mode) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Amount</dt>
                        <dd>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Created</dt>
                        <dd>{{ $payment->created_at?->toDayDateTimeString() ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Associated Booking</h3>
                @if ($payment->booking)
                    <dl class="mt-4 space-y-2 text-sm text-gray-700">
                        <div>
                            <dt class="font-medium text-gray-500">Booking ID</dt>
                            <dd>
                                <a href="{{ route('admin.bookings.show', $payment->booking) }}" class="text-indigo-600 hover:text-indigo-500">
                                    #{{ $payment->booking->id }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Status</dt>
                            <dd>{{ ucfirst($payment->booking->status) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Referral</dt>
                            <dd>{{ $payment->booking->referral_code ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Amount</dt>
                            <dd>{{ $payment->booking->currency }} {{ number_format($payment->booking->amount_final, 2) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-2 text-sm text-gray-500">No booking linked.</p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Raw Payload</h3>
                <pre class="mt-3 overflow-x-auto rounded bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode(json_decode($payment->raw_payload, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>
