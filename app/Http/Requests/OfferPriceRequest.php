<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use JsonException;

class OfferPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offer_token' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function decodedOffer(): array
    {
        $token = $this->input('offer_token');

        if (!$token) {
            throw ValidationException::withMessages([
                'offer_token' => 'Offer token is required.',
            ]);
        }

        $decoded = base64_decode($token, true);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                'offer_token' => 'Offer token could not be decoded.',
            ]);
        }

        try {
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'offer_token' => 'Offer token payload is invalid.',
            ]);
        }

        if (!is_array($payload)) {
            throw ValidationException::withMessages([
                'offer_token' => 'Offer token payload is invalid.',
            ]);
        }

        return $payload;
    }
}
