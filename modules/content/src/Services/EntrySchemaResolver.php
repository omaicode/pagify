<?php

namespace Pagify\Content\Services;

use Illuminate\Validation\Rule;
use Pagify\Content\Models\ContentType;

class EntrySchemaResolver
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function buildValidationRules(ContentType $contentType): array
    {
        $rules = [
            'slug' => ['required', 'string', 'max:160', 'alpha_dash'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published', 'scheduled'])],
            'data' => ['required', 'array'],
        ];

        foreach ($contentType->fields as $field) {
            $key = (string) $field->key;
            $ruleKey = 'data.' . $key;
            $fieldRules = [];
            $fieldType = (string) $field->field_type;
            $config = (array) ($field->config_json ?? []);

            if ((bool) $field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($fieldType === 'relation') {
                $relationType = (string) ($config['relation_type'] ?? 'hasOne');

                if ($relationType === 'hasOne') {
                    $fieldRules[] = 'integer';
                } else {
                    $fieldRules[] = 'array';
                    $rules[$ruleKey . '.*'] = ['integer'];
                }
            } else {
                foreach ($this->rulesByFieldType($fieldType, $config) as $rule) {
                    $fieldRules[] = $rule;
                }
            }

            $customRules = (array) ($field->validation_json['rules'] ?? []);
            foreach ($customRules as $customRule) {
                if (is_string($customRule) && $customRule !== '') {
                    $fieldRules[] = $customRule;
                }
            }

            $rules[$ruleKey] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    private function rulesByFieldType(string $fieldType, array $config): array
    {
        return match ($fieldType) {
            'text', 'richtext', 'media', 'conditional' => ['string'],
            'number' => ['numeric'],
            'date' => ['date'],
            'boolean' => ['boolean'],
            'select' => [Rule::in((array) ($config['options'] ?? []))],
            'relation' => ['string'],
            'repeater' => ['array'],
            default => ['string'],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formDefinition(ContentType $contentType): array
    {
        return $contentType->fields
            ->map(static fn ($field): array => [
                'key' => $field->key,
                'label' => $field->label,
                'field_type' => $field->field_type,
                'config' => $field->config_json ?? [],
                'validation' => $field->validation_json ?? [],
                'conditional' => $field->conditional_json ?? [],
                'is_required' => (bool) $field->is_required,
                'is_localized' => (bool) $field->is_localized,
            ])
            ->values()
            ->all();
    }
}
