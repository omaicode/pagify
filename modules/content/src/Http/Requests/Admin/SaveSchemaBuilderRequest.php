<?php

namespace Modules\Content\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Content\Http\Requests\Admin\Concerns\HasSchemaFieldRules;

class SaveSchemaBuilderRequest extends FormRequest
{
    use HasSchemaFieldRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->schemaFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return trans('content::validation.messages');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('content::validation.attributes');
    }
}
