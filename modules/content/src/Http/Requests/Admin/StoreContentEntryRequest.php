<?php

namespace Modules\Content\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentEntryRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:160', 'alpha_dash'],
            'status' => ['nullable', 'string'],
            'data' => ['required', 'array'],
            'scheduled_publish_at' => ['nullable', 'date'],
            'scheduled_unpublish_at' => ['nullable', 'date', 'after:scheduled_publish_at'],
        ];
    }
}
