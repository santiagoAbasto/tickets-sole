<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogWhatsappRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:20000'],
            'template_key' => ['nullable', 'string', 'max:50'],
            'save_note' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'No hay mensaje para registrar.',
        ];
    }
}
