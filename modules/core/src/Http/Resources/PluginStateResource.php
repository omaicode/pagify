<?php

namespace Pagify\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginStateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => (string) ($this['slug'] ?? ''),
            'name' => (string) ($this['name'] ?? ''),
            'description' => (string) ($this['description'] ?? ''),
            'version' => (string) ($this['version'] ?? ''),
            'enabled' => (bool) ($this['enabled'] ?? false),
            'is_installed' => (bool) ($this['is_installed'] ?? false),
            'source_type' => $this['source_type'] ?? null,
            'package_name' => $this['package_name'] ?? null,
            'is_compatible' => (bool) ($this['is_compatible'] ?? true),
            'compatibility_issues' => $this['compatibility_issues'] ?? [],
            'safe_mode_disabled_at' => $this['safe_mode_disabled_at'] ?? null,
            'last_error' => $this['last_error'] ?? null,
        ];
    }
}
