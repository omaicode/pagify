<?php

namespace Pagify\Content\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'status' => $this->status,
            'published_at' => optional($this->published_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
            'content_type' => [
                'id' => $this->contentType?->id,
                'slug' => $this->contentType?->slug,
                'name' => $this->contentType?->name,
            ],
            'data' => (array) ($this->data_json ?? []),
            'relations' => (array) ($this->getAttribute('resolved_relations') ?? []),
        ];
    }
}
