<?php

namespace Pagify\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InstallComposerPluginRequest extends FormRequest
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
            'package_name' => ['required', 'string', 'max:190'],
        ];
    }
}
