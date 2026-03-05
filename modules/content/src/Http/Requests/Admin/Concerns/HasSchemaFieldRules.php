<?php

namespace Pagify\Content\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Rule;

trait HasSchemaFieldRules
{
    /**
     * @return array<string, mixed>
     */
    protected function schemaFieldRules(): array
    {
        $fieldTypes = (array) config('content.field_types', []);
        $relationTypes = (array) config('content.relation_types', []);

        return [
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'max:120', 'alpha_dash'],
            'fields.*.label' => ['required', 'string', 'max:160'],
            'fields.*.field_type' => ['required', 'string', Rule::in($fieldTypes)],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_localized' => ['nullable', 'boolean'],
            'fields.*.config' => ['nullable', 'array'],
            'fields.*.validation' => ['nullable', 'array'],
            'fields.*.conditional' => ['nullable', 'array'],
            'fields.*.config.options' => ['nullable', 'array'],
            'fields.*.config.options.*' => ['nullable', 'string', 'max:120'],
            'fields.*.config.relation_type' => ['nullable', 'string', Rule::in($relationTypes)],
            'fields.*.config.target_content_type_slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
            'fields.*.config.target_type_slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
            'fields.*.config.fields' => ['nullable', 'array'],
        ];
    }
}
