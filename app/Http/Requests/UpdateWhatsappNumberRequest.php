<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsappNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('notifyCustomer', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Ingresá un número de WhatsApp.',
        ];
    }
}
