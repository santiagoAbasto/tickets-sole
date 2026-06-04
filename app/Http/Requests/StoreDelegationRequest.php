<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delegate', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'requested_to' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('is_agent', true)->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ticket = $this->route('ticket');

            if ((int) $this->input('requested_to') === (int) $ticket->assigned_to) {
                $validator->errors()->add('requested_to', 'El ticket ya está asignado a esa persona.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'requested_to.required' => 'Elegí a quién querés delegar el soporte.',
            'requested_to.exists' => 'Seleccioná un agente válido.',
        ];
    }
}
