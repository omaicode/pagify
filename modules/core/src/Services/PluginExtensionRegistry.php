<?php

namespace Pagify\Core\Services;

class PluginExtensionRegistry
{
    public function __construct(
        private readonly PluginManagerService $plugins,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function fieldTypes(): array
    {
        $items = $this->plugins->extensionPoints()['field_types'] ?? [];

        return $this->normalizeScalarValues($items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menuItems(): array
    {
        $items = $this->plugins->extensionPoints()['menu_items'] ?? [];
        $resolved = [];

        foreach ($items as $item) {
            $value = $item['value'] ?? null;

            if (! is_array($value)) {
                continue;
            }

            $resolved[] = [
                ...$value,
                'plugin' => $item['plugin'] ?? null,
            ];
        }

        return $resolved;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function all(): array
    {
        return $this->plugins->extensionPoints();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, string>
     */
    private function normalizeScalarValues(array $items): array
    {
        $values = [];

        foreach ($items as $item) {
            $value = $item['value'] ?? null;

            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }
}
