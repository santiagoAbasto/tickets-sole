<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        $rules = [
            'phone' => ['nullable', 'string', 'max:40'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];

        // Identity fields are editable only by Super Admin (enforced server-side).
        if ($this->user()->hasRole('Super Admin')) {
            $rules['name'] = ['required', 'string', 'max:120'];
            $rules['email'] = ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($userId)];
            $rules['job_title'] = ['nullable', 'string', 'max:120'];
        }

        return $rules;
    }
}
