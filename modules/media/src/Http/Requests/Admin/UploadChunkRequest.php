<?php

namespace Pagify\Media\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadChunkRequest extends FormRequest
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
            'chunk_index' => ['required', 'integer', 'min:1'],
            'chunk' => ['required', 'file'],
        ];
    }
}
