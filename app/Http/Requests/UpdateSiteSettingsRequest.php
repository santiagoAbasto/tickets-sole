<?php

namespace App\Http\Requests;

use App\Support\Whatsapp;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('Super Admin');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['whatsapp_enabled' => $this->boolean('whatsapp_enabled')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_enabled' => ['boolean'],
            'whatsapp_greeting' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $number = $this->input('whatsapp_number');

            if (filled($number) && ! Whatsapp::normalize($number)) {
                $validator->errors()->add(
                    'whatsapp_number',
                    'Número no válido. Incluí el código de país, por ejemplo +54 9 11 1234 5678.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'whatsapp_greeting.max' => 'El saludo es demasiado largo (máximo 500 caracteres).',
        ];
    }
}
