<?php

namespace Pagify\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleStateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this['slug'],
            'name' => $this['name'],
            'description' => $this['description'],
            'enabled' => (bool) $this['enabled'],
            'can_disable' => (bool) ($this['can_disable'] ?? true),
        ];
    }
}
