<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', 'exists:ticket_statuses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'status_id.required' => 'Seleccioná un estado.',
        ];
    }
}
