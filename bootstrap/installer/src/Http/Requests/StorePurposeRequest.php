<?php

namespace Pagify\Installer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurposeRequest extends FormRequest
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
            'purpose' => ['required', 'string', 'in:blog,company,ecommerce,other'],
            'custom_purpose' => ['nullable', 'string', 'max:255'],
        ];
    }
}
