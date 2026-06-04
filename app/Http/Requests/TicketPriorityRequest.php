<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('settings.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('priority')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('ticket_priorities', 'slug')->ignore($id)],
            'color' => ['required', 'string', 'max:20'],
            'response_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'resolution_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
