<?php

namespace Pagify\Core\Services;

use Pagify\Core\Models\Setting;
use Pagify\Core\Support\SiteContext;

class SettingsManager
{
    public function __construct(private readonly SiteContext $siteContext)
    {
    }

    public function get(string $key, mixed $default = null, ?int $siteId = null): mixed
    {
        $effectiveSiteId = $siteId ?? $this->siteContext->siteId();

        $query = Setting::query()->where('key', $key);

        if ($effectiveSiteId !== null) {
            $siteValue = (clone $query)->where('site_id', $effectiveSiteId)->first();

            if ($siteValue !== null) {
                return $siteValue->value;
            }
        }

        $globalValue = (clone $query)->whereNull('site_id')->first();

        return $globalValue?->value ?? $default;
    }

    public function set(string $key, mixed $value, ?int $siteId = null): void
    {
        $effectiveSiteId = $siteId ?? $this->siteContext->siteId();

        Setting::query()->updateOrCreate(
            [
                'key' => $key,
                'site_id' => $effectiveSiteId,
            ],
            [
                'value' => $value,
            ]
        );
    }
}
