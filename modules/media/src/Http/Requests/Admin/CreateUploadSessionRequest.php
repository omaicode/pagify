<?php

namespace Pagify\Media\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateUploadSessionRequest extends FormRequest
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
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:160'],
            'total_size_bytes' => ['required', 'integer', 'min:1'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:10000'],
            'folder_id' => ['nullable', 'integer'],
        ];
    }
}
