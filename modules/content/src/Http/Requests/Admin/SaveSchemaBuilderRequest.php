<?php

namespace Modules\Content\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveSchemaBuilderRequest extends FormRequest
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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'max:120'],
            'fields.*.label' => ['required', 'string', 'max:160'],
            'fields.*.field_type' => ['required', 'string'],
            'fields.*.sort_order' => ['nullable', 'integer'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_localized' => ['nullable', 'boolean'],
            'fields.*.config' => ['nullable'],
            'fields.*.validation' => ['nullable'],
            'fields.*.conditional' => ['nullable'],
        ];
    }
}
