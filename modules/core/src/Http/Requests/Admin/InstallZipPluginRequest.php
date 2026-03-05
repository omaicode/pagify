<?php

namespace Pagify\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InstallZipPluginRequest extends FormRequest
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
            'plugin_zip' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ];
    }
}
