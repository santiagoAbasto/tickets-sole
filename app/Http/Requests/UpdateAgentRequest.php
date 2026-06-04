<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('agent');

        if (! $this->user()->hasPermissionTo('agents.manage') || ! $target) {
            return false;
        }

        if ($this->user()->hasRole('Super Admin')) {
            return true;
        }

        return $target->hasRole('Agente');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $agentId = $this->route('agent')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($agentId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'role' => ['required', Rule::in($this->assignableRoles())],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
        ];
    }

    public function assignableRoles(): array
    {
        return $this->user()->hasRole('Super Admin')
            ? ['Agente', 'Admin', 'Super Admin']
            : ['Agente'];
    }
}
