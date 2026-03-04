<?php

namespace Modules\Content\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ListContentEntriesRequest extends FormRequest
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
        return [
            'status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:120'],
            'sort_by' => ['nullable', 'string', 'in:id,slug,created_at,published_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'filter' => ['nullable', 'array'],
            'filter.*' => ['nullable'],
        ];
    }
}
