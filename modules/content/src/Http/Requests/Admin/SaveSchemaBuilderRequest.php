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
}
