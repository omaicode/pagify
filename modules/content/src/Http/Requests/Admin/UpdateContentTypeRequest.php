<?php

namespace Pagify\Content\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pagify\Content\Http\Requests\Admin\Concerns\HasSchemaFieldRules;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Support\SiteContext;

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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return trans('content::validation.messages');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('content::validation.attributes');
    }
}
