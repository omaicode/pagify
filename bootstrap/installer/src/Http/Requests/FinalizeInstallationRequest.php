<?php

namespace Pagify\Installer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeInstallationRequest extends FormRequest
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
            'site.name' => ['nullable', 'string', 'max:190'],
            'site.slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'site.domain' => ['nullable', 'string', 'max:190'],
            'site.locale' => ['nullable', 'string', 'max:8'],
        ];
    }
}
