<?php

namespace Pagify\Content\Services;

class EntryDiffService
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<string, mixed>
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (['slug', 'status'] as $topLevelKey) {
            $beforeValue = $before[$topLevelKey] ?? null;
            $afterValue = $after[$topLevelKey] ?? null;

            if ($beforeValue !== $afterValue) {
                $changes[] = [
                    'key' => $topLevelKey,
                    'before' => $beforeValue,
                    'after' => $afterValue,
                ];
            }
        }

        $beforeData = (array) ($before['data'] ?? []);
        $afterData = (array) ($after['data'] ?? []);
        $keys = array_values(array_unique([...array_keys($beforeData), ...array_keys($afterData)]));

        foreach ($keys as $key) {
            $beforeValue = $beforeData[$key] ?? null;
            $afterValue = $afterData[$key] ?? null;

            if ($beforeValue !== $afterValue) {
                $changes[] = [
                    'key' => 'data.' . $key,
                    'before' => $beforeValue,
                    'after' => $afterValue,
                ];
            }
        }

        return [
            'changed' => $changes !== [],
            'changes' => $changes,
        ];
    }
}
