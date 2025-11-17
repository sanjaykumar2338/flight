<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class TravelNdcOrderRequest extends OfferPriceRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'ptc' => ['required', 'string', Rule::in(['ADT', 'CHD', 'INF'])],
            'birthdate' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', Rule::in(['MALE', 'FEMALE'])],
            'title' => ['required', 'string', 'max:10'],
            'given_name' => ['required', 'string', 'max:80'],
            'surname' => ['required', 'string', 'max:80'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required', 'string', 'max:40'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ptc' => strtoupper((string) $this->input('ptc', 'ADT')),
            'gender' => strtoupper((string) $this->input('gender', 'MALE')),
            'title' => strtoupper((string) $this->input('title', 'MR')),
            'given_name' => trim((string) $this->input('given_name')),
            'surname' => trim((string) $this->input('surname')),
            'contact_email' => trim((string) $this->input('contact_email')),
            'contact_phone' => trim((string) $this->input('contact_phone')),
        ]);
    }
}
