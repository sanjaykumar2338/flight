<?php

namespace App\Http\Requests;

class VidecomHoldRequest extends OfferPriceRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'passenger_title' => ['required', 'string', 'max:10'],
            'passenger_first_name' => ['required', 'string', 'max:80'],
            'passenger_last_name' => ['required', 'string', 'max:80'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:40'],
        ]);
    }
}
