<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // null = unassign; otherwise must be an active agent.
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_agent', true)->where('is_active', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'El agente seleccionado no es válido.',
        ];
    }
}
