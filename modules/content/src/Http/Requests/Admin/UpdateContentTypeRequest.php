<?php

namespace Modules\Content\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Content\Http\Requests\Admin\Concerns\HasSchemaFieldRules;
use Modules\Content\Models\ContentType;
use Modules\Core\Support\SiteContext;

class UpdateContentTypeRequest extends FormRequest
{
    use HasSchemaFieldRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $siteId = app(SiteContext::class)->siteId();
        /** @var ContentType|null $contentType */
        $contentType = $this->route('contentType');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('content_types', 'slug')
                    ->where(static fn ($query) => $query->where('site_id', $siteId))
                    ->ignore($contentType?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            ...$this->schemaFieldRules(),
        ];
    }
}
