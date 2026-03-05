<?php

namespace Pagify\Media\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListMediaAssetsRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'kind' => ['nullable', 'string', 'in:image,video,document,other'],
            'folder_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:id,created_at,size_bytes,filename'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'view' => ['nullable', 'string', 'in:grid,list'],
        ];
    }
}
