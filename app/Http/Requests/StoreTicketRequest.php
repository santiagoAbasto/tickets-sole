<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:20000'],
            // Customer is resolved by email (created if it doesn't exist yet).
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:180'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:ticket_statuses,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            // Internal-only hosting/cPanel access (optional).
            'cpanel_user' => ['nullable', 'string', 'max:190'],
            'cpanel_password' => ['nullable', 'string', 'max:190'],
            'server_url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/[^\s]+$/i'],
            'hosting_type' => ['nullable', 'in:osole,external'],
            'hosting_provider' => ['nullable', 'string', 'max:190'],
            'credentials_notes' => ['nullable', 'string', 'max:2000'],
        ], $this->attachmentRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge([
            'subject.required' => 'El asunto es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'customer_name.required' => 'Ingresá el nombre del cliente.',
            'customer_email.required' => 'Ingresá el email del cliente.',
            'customer_email.email' => 'El email del cliente no es válido.',
            'category_id.required' => 'Seleccioná una categoría.',
            'priority_id.required' => 'Seleccioná una prioridad.',
        ], $this->attachmentMessages());
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripción',
            'customer_name' => 'nombre del cliente',
            'customer_email' => 'email del cliente',
            'category_id' => 'categoría',
            'priority_id' => 'prioridad',
        ];
    }
}
