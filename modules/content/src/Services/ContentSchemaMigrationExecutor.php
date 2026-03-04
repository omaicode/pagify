<?php

namespace Modules\Content\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Content\Models\ContentSchemaMigrationPlan;

class ContentSchemaMigrationExecutor
{
    public function execute(ContentSchemaMigrationPlan $plan): ContentSchemaMigrationPlan
    {
        $plan = $plan->withoutGlobalScopes()->newQuery()->findOrFail($plan->id);

        $executionHash = hash('sha256', json_encode([
            'content_type_id' => $plan->content_type_id,
            'before' => $plan->schema_before_json,
            'after' => $plan->schema_after_json,
        ], JSON_UNESCAPED_UNICODE));

        if ($plan->status === 'applied' && $plan->execution_hash === $executionHash) {
            return $plan;
        }

        $operations = (array) Arr::get($plan->plan_json ?? [], 'operations', []);
        $tableName = $this->tableName($plan->content_type_id);

        $plan->fill([
            'status' => 'executing',
            'error_message' => null,
            'execution_hash' => $executionHash,
            'execution_attempts' => ((int) $plan->execution_attempts) + 1,
            'execution_started_at' => Carbon::now(),
        ])->save();

        $this->ensureTable($tableName);

        foreach ((array) ($operations['add'] ?? []) as $field) {
            $this->addFieldColumn($tableName, (array) $field);
        }

        foreach ((array) ($operations['update'] ?? []) as $update) {
            $before = (array) Arr::get((array) $update, 'before', []);
            $after = (array) Arr::get((array) $update, 'after', []);

            $beforeKey = (string) Arr::get($before, 'key', '');
            $afterKey = (string) Arr::get($after, 'key', '');

            if ($beforeKey !== '' && $beforeKey !== $afterKey) {
                $this->dropFieldColumn($tableName, $beforeKey);
            }

            if ($afterKey !== '') {
                $this->dropFieldColumn($tableName, $afterKey);
                $this->addFieldColumn($tableName, $after);
            }
        }

        foreach ((array) ($operations['remove'] ?? []) as $field) {
            $this->dropFieldColumn($tableName, (string) Arr::get((array) $field, 'key', ''));
        }

        $plan->fill([
            'status' => 'applied',
            'executed_at' => Carbon::now(),
            'error_message' => null,
        ])->save();

        return $plan->refresh();
    }

    private function ensureTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('content_entry_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * @param array<string, mixed> $field
     */
    private function addFieldColumn(string $tableName, array $field): void
    {
        $key = (string) Arr::get($field, 'key', '');

        if ($key === '' || Schema::hasColumn($tableName, $key)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($field, $key): void {
            $this->applyColumn($table, $key, (string) Arr::get($field, 'field_type', 'text'));
        });
    }

    private function dropFieldColumn(string $tableName, string $key): void
    {
        if ($key === '' || ! Schema::hasColumn($tableName, $key)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($key): void {
            $table->dropColumn($key);
        });
    }

    private function applyColumn(Blueprint $table, string $column, string $fieldType): void
    {
        $type = strtolower($fieldType);

        match ($type) {
            'number' => $table->decimal($column, 20, 6)->nullable(),
            'date' => $table->dateTime($column)->nullable(),
            'boolean' => $table->boolean($column)->nullable(),
            'repeater', 'conditional' => $table->json($column)->nullable(),
            'relation' => $table->unsignedBigInteger($column)->nullable()->index(),
            'richtext', 'media' => $table->longText($column)->nullable(),
            default => $table->text($column)->nullable(),
        };
    }

    private function tableName(int $contentTypeId): string
    {
        return 'content_type_data_' . $contentTypeId;
    }
}
