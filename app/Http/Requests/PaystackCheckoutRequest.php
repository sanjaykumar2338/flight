<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaystackCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:191'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->filled('email')) {
            $this->merge(['email' => $this->user()->email]);
        }

        if ($this->user() && !$this->filled('name')) {
            $this->merge(['name' => $this->user()->name]);
        }
    }
}
