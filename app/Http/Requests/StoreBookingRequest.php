<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package' => ['required', 'string', 'max:50'],
            'departure' => ['required', 'date', 'after_or_equal:today'],
            'travelers' => ['required', 'integer', 'between:1,6'],
            'origin' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,filter', 'max:254'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'package.required' => 'Please select a travel package.',
            'departure.required' => 'Please choose a departure date.',
            'departure.after_or_equal' => 'Departure date cannot be in the past.',
            'travelers.between' => 'Travelers count must be between 1 and 6.',
            'origin.required' => 'Please enter departure city or airport.',
            'name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter a valid email address.',
            'email.email' => 'Please enter a valid email address format.',
        ];
    }
}
