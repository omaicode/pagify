<?php

namespace Pagify\Media\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaAssetRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'integer'],
            'focal_point_x' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'focal_point_y' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ];
    }
}
