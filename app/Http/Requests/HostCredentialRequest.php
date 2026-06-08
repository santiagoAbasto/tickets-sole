<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HostCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required_without_all:website_url,server_url,cpanel_user,hosting_provider', 'nullable', 'string', 'max:190'],
            'website_url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/[^\s]+$/i'],
            'server_url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/[^\s]+$/i'],
            'hosting_type' => ['nullable', 'in:osole,external'],
            'hosting_provider' => ['nullable', 'string', 'max:190'],
            'cpanel_user' => ['nullable', 'string', 'max:190'],
            'cpanel_password' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
