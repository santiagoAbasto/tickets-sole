<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPublicTicketRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return true; // public endpoint
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            // Which website / system the issue is about.
            'site_url' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:20000'],
            // Honeypot: must stay empty. Bots fill every field.
            'company_fax' => ['prohibited'],
        ], $this->attachmentRules());
    }

    public function messages(): array
    {
        return array_merge([
            'name.required' => 'Decinos tu nombre.',
            'email.required' => 'Necesitamos tu email para responderte.',
            'email.email' => 'Ingresá un email válido.',
            'subject.required' => 'Contanos el asunto.',
            'category_id.required' => 'Elegí una categoría.',
            'description.required' => 'Describí tu consulta.',
            'description.min' => 'Agregá un poco más de detalle (mínimo 10 caracteres).',
        ], $this->attachmentMessages());
    }

    public function attributes(): array
    {
        return ['category_id' => 'categoría'];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('error', 'No pudimos enviar la consulta. Revisá los campos marcados y probá de nuevo.');

        parent::failedValidation($validator);
    }
}
