<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        // All staff who can see the ticket may manage its internal credentials.
        return $this->user()->isStaff() && $this->user()->can('view', $ticket);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cpanel_user' => ['nullable', 'string', 'max:190'],
            'cpanel_password' => ['nullable', 'string', 'max:190'],
            'server_url' => ['nullable', 'string', 'max:500'],
            'hosting_type' => ['nullable', 'in:osole,external'],
            'hosting_provider' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
