<?php

namespace App\Http\Requests\Concerns;

trait ValidatesAttachments
{
    /** Allowed upload types, shared across ticket forms. */
    public const ALLOWED_MIMES = 'jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip';

    /** Max size per file in kilobytes (10 MB). */
    public const MAX_KB = 10240;

    /**
     * Validation rules for an optional `attachments[]` field.
     *
     * @return array<string, mixed>
     */
    protected function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:'.self::ALLOWED_MIMES, 'max:'.self::MAX_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function attachmentMessages(): array
    {
        return [
            'attachments.*.mimes' => 'Formato no permitido. Aceptados: '.self::ALLOWED_MIMES.'.',
            'attachments.*.max' => 'Cada archivo puede pesar hasta 10 MB.',
            'attachments.max' => 'Podés adjuntar hasta 10 archivos.',
        ];
    }
}
