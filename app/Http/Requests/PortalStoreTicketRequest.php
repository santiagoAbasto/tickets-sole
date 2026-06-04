<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class PortalStoreTicketRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * Customers only choose subject, description, category and priority.
     * customer/company/status/agent are derived server-side, never trusted.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:20000'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
        ], $this->attachmentRules());
    }

    public function messages(): array
    {
        return array_merge([
            'subject.required' => 'Contanos el asunto.',
            'description.required' => 'Describí tu consulta.',
            'category_id.required' => 'Elegí una categoría.',
            'priority_id.required' => 'Elegí una prioridad.',
        ], $this->attachmentMessages());
    }
}
