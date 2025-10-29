<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Legacy Airline Commissions') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                The new <a href="{{ route('admin.pricing.index') }}" class="font-semibold underline">Pricing Rules</a> experience now manages commissions, discounts, and fees.
                This legacy screen remains available for reference during rollout.
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Create Commission Rule</h3>
                <p class="mt-1 text-sm text-gray-500">Define the default markup you want to apply for a carrier.</p>

                <form method="POST" action="{{ route('admin.airline-commissions.store') }}" class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @csrf
                    <div>
                        <x-input-label for="airline_code" value="Airline Code (IATA)" />
                        <x-text-input id="airline_code" name="airline_code" type="text" maxlength="3" class="mt-1 block w-full uppercase" value="{{ old('airline_code') }}" required />
                        <x-input-error :messages="$errors->get('airline_code')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="airline_name" value="Airline Name" />
                        <x-text-input id="airline_name" name="airline_name" type="text" class="mt-1 block w-full" value="{{ old('airline_name') }}" />
                        <x-input-error :messages="$errors->get('airline_name')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4 md:col-span-2 lg:col-span-1">
                        <div>
                            <x-input-label for="markup_percent" value="Markup (%)" />
                            <x-text-input id="markup_percent" name="markup_percent" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('markup_percent', 5) }}" required />
                            <x-input-error :messages="$errors->get('markup_percent')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="flat_markup" value="Flat Markup" />
                            <x-text-input id="flat_markup" name="flat_markup" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('flat_markup', 0) }}" required />
                            <x-input-error :messages="$errors->get('flat_markup')" class="mt-2" />
                        </div>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <x-input-label for="notes" value="Notes" />
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>
                    <div class="flex items-center gap-2 md:col-span-2 lg:col-span-3">
                        <input type="hidden" name="is_active" value="0" />
                        <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', true) ? 'checked' : '' }} />
                        <label for="is_active" class="text-sm text-gray-700">Commission is active</label>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3 text-right">
                        <x-primary-button>{{ __('Save Commission') }}</x-primary-button>
                    </div>
                </form>

                @if (session('status'))
                    <div class="mt-4 rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Existing Commissions</h3>
                <p class="mt-1 text-sm text-gray-500">Adjust markup values or disable a carrier when needed.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Airline</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Percent</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Flat</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Active</th>
                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                                <th class="px-4 py-2 text-right font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($commissions as $commission)
                                @php($formId = 'commission-update-' . $commission->id)
                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.airline-commissions.update', $commission) }}">
                                    @csrf
                                    @method('PUT')
                                </form>
                                <tr>
                                    <td class="px-4 py-2 align-top">
                                        <div class="font-semibold text-gray-900">{{ $commission->airline_code }}</div>
                                        <div class="text-xs text-gray-500">{{ $commission->airline_name ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <x-text-input form="{{ $formId }}" name="markup_percent" type="number" step="0.01" min="0" class="block w-24" value="{{ old('markup_percent', $commission->markup_percent) }}" />
                                        <x-input-error :messages="$errors->get('markup_percent')" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <x-text-input form="{{ $formId }}" name="flat_markup" type="number" step="0.01" min="0" class="block w-24" value="{{ old('flat_markup', $commission->flat_markup) }}" />
                                        <x-input-error :messages="$errors->get('flat_markup')" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input form="{{ $formId }}" type="hidden" name="is_active" value="0" />
                                            <input form="{{ $formId }}" type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $commission->is_active ? 'checked' : '' }} />
                                            <span>Active</span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <textarea form="{{ $formId }}" name="notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $commission->notes) }}</textarea>
                                    </td>
                                    <td class="px-4 py-2 align-top text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-primary-button form="{{ $formId }}">{{ __('Update') }}</x-primary-button>
                                            <form method="POST" action="{{ route('admin.airline-commissions.destroy', $commission) }}" onsubmit="return confirm('Delete commission for {{ $commission->airline_code }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button>{{ __('Delete') }}</x-danger-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No commission records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
