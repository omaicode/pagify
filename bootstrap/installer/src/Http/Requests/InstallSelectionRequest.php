<?php

namespace Pagify\Installer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slugs' => ['required', 'array', 'min:1'],
            'slugs.*' => ['required', 'string', 'max:120'],
        ];
    }
}
