<?php

namespace Modules\Content\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;

class ContentEntryService
{
    public function __construct(private readonly EntrySchemaResolver $schemaResolver)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(ContentType $contentType, array $payload): ContentEntry
    {
        $validated = $this->validatePayload($contentType, $payload);

        return DB::transaction(function () use ($contentType, $validated): ContentEntry {
            return ContentEntry::query()->create([
                'content_type_id' => $contentType->id,
                'slug' => (string) $validated['slug'],
                'status' => (string) ($validated['status'] ?? 'draft'),
                'data_json' => $this->normalizeData($contentType, (array) $validated['data']),
            ]);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(ContentType $contentType, ContentEntry $entry, array $payload): ContentEntry
    {
        $validated = $this->validatePayload($contentType, $payload, $entry);

        return DB::transaction(function () use ($entry, $contentType, $validated): ContentEntry {
            $entry->fill([
                'slug' => (string) $validated['slug'],
                'status' => (string) ($validated['status'] ?? $entry->status),
                'data_json' => $this->normalizeData($contentType, (array) $validated['data']),
            ])->save();

            return $entry->refresh();
        });
    }

    public function delete(ContentEntry $entry): void
    {
        $entry->delete();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function validatePayload(ContentType $contentType, array $payload, ?ContentEntry $entry = null): array
    {
        $payload = $this->preprocessPayloadByFieldType($contentType, $payload);

        $rules = $this->schemaResolver->buildValidationRules($contentType);
        $rules['slug'][] = Rule::unique('content_entries', 'slug')
            ->where(static fn ($query) => $query
                ->where('site_id', $contentType->site_id)
                ->where('content_type_id', $contentType->id)
            )
            ->ignore($entry?->id);

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function preprocessPayloadByFieldType(ContentType $contentType, array $payload): array
    {
        $data = (array) ($payload['data'] ?? []);

        foreach ($contentType->fields as $field) {
            if ((string) $field->field_type !== 'repeater') {
                continue;
            }

            $key = (string) $field->key;

            if (! array_key_exists($key, $data)) {
                continue;
            }

            if (is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);

                if (is_array($decoded)) {
                    $data[$key] = $decoded;
                }
            }
        }

        $payload['data'] = $data;

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeData(ContentType $contentType, array $data): array
    {
        $allowedKeys = $contentType->fields->pluck('key')->map(static fn ($key): string => (string) $key)->all();
        $result = Arr::only($data, $allowedKeys);

        foreach ($allowedKeys as $allowedKey) {
            if (! array_key_exists($allowedKey, $result)) {
                $result[$allowedKey] = null;
            }
        }

        return $result;
    }
}
