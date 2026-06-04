<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()->can('reply', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'body' => ['required', 'string', 'max:20000'],
        ], $this->attachmentRules());
    }

    public function messages(): array
    {
        return array_merge([
            'body.required' => 'Escribí una respuesta.',
        ], $this->attachmentMessages());
    }
}
