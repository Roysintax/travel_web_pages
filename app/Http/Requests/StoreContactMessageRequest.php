<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc,filter', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'topic' => ['required', 'string', 'max:100'],
            'reply' => ['nullable', 'string', 'in:Email,WhatsApp,Phone call'],
            'message' => ['required', 'string', 'min:10', 'max:600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.min' => 'Please enter your name (at least 2 characters).',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'message.required' => 'Tell us a little more (at least 10 characters).',
            'message.min' => 'Tell us a little more (at least 10 characters).',
            'message.max' => 'Your message cannot exceed 600 characters.',
        ];
    }
}
