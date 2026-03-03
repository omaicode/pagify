<?php

namespace Modules\Content\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Content\Models\ContentType;

class ContentTypeService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): ContentType
    {
        $normalizedFields = $this->normalizeFields($payload['fields'] ?? []);

        return DB::transaction(function () use ($payload, $normalizedFields): ContentType {
            $contentType = ContentType::query()->create([
                'name' => (string) $payload['name'],
                'slug' => (string) $payload['slug'],
                'description' => $payload['description'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'schema_json' => [
                    'version' => 1,
                    'fields' => $normalizedFields,
                ],
            ]);

            $this->syncFields($contentType, $normalizedFields);

            return $contentType->load('fields');
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(ContentType $contentType, array $payload): ContentType
    {
        $normalizedFields = $this->normalizeFields($payload['fields'] ?? []);

        return DB::transaction(function () use ($contentType, $payload, $normalizedFields): ContentType {
            $contentType->fill([
                'name' => (string) $payload['name'],
                'slug' => (string) $payload['slug'],
                'description' => $payload['description'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'schema_json' => [
                    'version' => 1,
                    'fields' => $normalizedFields,
                ],
            ])->save();

            $this->syncFields($contentType, $normalizedFields);

            return $contentType->load('fields');
        });
    }

    public function delete(ContentType $contentType): void
    {
        DB::transaction(static function () use ($contentType): void {
            $contentType->fields()->delete();
            $contentType->delete();
        });
    }

    /**
     * @param mixed $fields
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFields(mixed $fields): array
    {
        if (! is_array($fields) || $fields === []) {
            throw ValidationException::withMessages([
                'fields' => ['At least one field is required.'],
            ]);
        }

        $allowedTypes = config('content.field_types', []);
        $normalized = [];
        $keys = [];

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                throw ValidationException::withMessages([
                    'fields.' . $index => ['Field definition is invalid.'],
                ]);
            }

            $key = (string) Arr::get($field, 'key', '');
            $label = (string) Arr::get($field, 'label', '');
            $fieldType = (string) Arr::get($field, 'field_type', '');

            if ($key === '' || $label === '' || $fieldType === '') {
                throw ValidationException::withMessages([
                    'fields.' . $index => ['Field key, label, and type are required.'],
                ]);
            }

            if (in_array($fieldType, $allowedTypes, true) === false) {
                throw ValidationException::withMessages([
                    'fields.' . $index . '.field_type' => ['Unsupported field type.'],
                ]);
            }

            if (in_array($key, $keys, true)) {
                throw ValidationException::withMessages([
                    'fields.' . $index . '.key' => ['Field key must be unique within a content type.'],
                ]);
            }

            $keys[] = $key;

            $config = $this->normalizeOptionalArray($field['config'] ?? []);
            $validation = $this->normalizeOptionalArray($field['validation'] ?? []);
            $conditional = $this->normalizeOptionalArray($field['conditional'] ?? []);

            $this->validateByFieldType($fieldType, $config, $index);

            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'field_type' => $fieldType,
                'config' => $config,
                'validation' => $validation,
                'conditional' => $conditional,
                'sort_order' => (int) ($field['sort_order'] ?? $index),
                'is_required' => (bool) ($field['is_required'] ?? false),
                'is_localized' => (bool) ($field['is_localized'] ?? false),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $normalized;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function validateByFieldType(string $fieldType, array $config, int $index): void
    {
        if ($fieldType === 'select' && (! isset($config['options']) || ! is_array($config['options']) || $config['options'] === [])) {
            throw ValidationException::withMessages([
                'fields.' . $index . '.config.options' => ['Select field requires config.options array.'],
            ]);
        }

        if ($fieldType === 'relation') {
            $relationTypes = config('content.relation_types', []);
            $relationType = (string) ($config['relation_type'] ?? '');
            $targetTypeSlug = (string) ($config['target_type_slug'] ?? '');

            if ($relationType === '' || ! in_array($relationType, $relationTypes, true) || $targetTypeSlug === '') {
                throw ValidationException::withMessages([
                    'fields.' . $index . '.config' => ['Relation field requires valid relation_type and target_type_slug.'],
                ]);
            }
        }

        if ($fieldType === 'repeater' && (! isset($config['fields']) || ! is_array($config['fields']))) {
            throw ValidationException::withMessages([
                'fields.' . $index . '.config.fields' => ['Repeater field requires config.fields array.'],
            ]);
        }
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function normalizeOptionalArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private function syncFields(ContentType $contentType, array $fields): void
    {
        $contentType->fields()->delete();

        foreach ($fields as $field) {
            $contentType->fields()->create([
                'key' => $field['key'],
                'label' => $field['label'],
                'field_type' => $field['field_type'],
                'config_json' => $field['config'],
                'validation_json' => $field['validation'],
                'conditional_json' => $field['conditional'],
                'sort_order' => $field['sort_order'],
                'is_required' => $field['is_required'],
                'is_localized' => $field['is_localized'],
            ]);
        }
    }
}
