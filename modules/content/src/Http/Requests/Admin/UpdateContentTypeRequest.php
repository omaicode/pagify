<?php

namespace Modules\Content\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Content\Models\ContentType;
use Modules\Core\Support\SiteContext;

class UpdateContentTypeRequest extends FormRequest
{
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
        $fieldTypes = config('content.field_types', []);
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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'max:120', 'alpha_dash'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.field_type' => ['required', 'string', Rule::in($fieldTypes)],
            'fields.*.config' => ['nullable'],
            'fields.*.validation' => ['nullable'],
            'fields.*.conditional' => ['nullable'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_localized' => ['nullable', 'boolean'],
        ];
    }
}
