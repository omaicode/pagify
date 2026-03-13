<?php

namespace Pagify\Installer\Services;

class MarketplaceCatalogService
{
    /**
     * @return array<string, mixed>
     */
    public function plugins(?string $purpose = null): array
    {
        $items = array_values((array) config('installer.marketplace.plugins', []));
        $recommended = $this->recommended('plugins', $purpose);

        $mapped = array_map(static function (array $item) use ($recommended): array {
            $slug = (string) ($item['slug'] ?? '');
            $item['recommended'] = in_array($slug, $recommended, true);

            return $item;
        }, $items);

        return [
            'items' => $mapped,
            'recommended_slugs' => $recommended,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function themes(?string $purpose = null): array
    {
        $items = array_values((array) config('installer.marketplace.themes', []));
        $recommended = $this->recommended('themes', $purpose);

        $mapped = array_map(static function (array $item) use ($recommended): array {
            $slug = (string) ($item['slug'] ?? '');
            $item['recommended'] = in_array($slug, $recommended, true);

            return $item;
        }, $items);

        return [
            'items' => $mapped,
            'recommended_slugs' => $recommended,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function recommended(string $type, ?string $purpose): array
    {
        $purposeKey = trim((string) $purpose);

        if ($purposeKey === '') {
            return [];
        }

        $purposeConfig = (array) config('installer.purposes.'.$purposeKey, []);
        $key = $type === 'themes' ? 'recommended_themes' : 'recommended_plugins';

        return array_values(array_filter(array_map('strval', (array) ($purposeConfig[$key] ?? [])), static fn (string $v): bool => $v !== ''));
    }

    /**
     * @param array<int, string> $slugs
     * @return array<int, array<string, mixed>>
     */
    public function findPluginPackagesBySlugs(array $slugs): array
    {
        $index = [];
        foreach ((array) config('installer.marketplace.plugins', []) as $item) {
            if (is_array($item) && isset($item['slug'])) {
                $index[(string) $item['slug']] = $item;
            }
        }

        $result = [];
        foreach ($slugs as $slug) {
            $normalized = trim((string) $slug);
            if ($normalized !== '' && isset($index[$normalized])) {
                $result[] = (array) $index[$normalized];
            }
        }

        return $result;
    }

    /**
     * @param array<int, string> $slugs
     * @return array<int, array<string, mixed>>
     */
    public function findThemePackagesBySlugs(array $slugs): array
    {
        $index = [];
        foreach ((array) config('installer.marketplace.themes', []) as $item) {
            if (is_array($item) && isset($item['slug'])) {
                $index[(string) $item['slug']] = $item;
            }
        }

        $result = [];
        foreach ($slugs as $slug) {
            $normalized = trim((string) $slug);
            if ($normalized !== '' && isset($index[$normalized])) {
                $result[] = (array) $index[$normalized];
            }
        }

        return $result;
    }
}
