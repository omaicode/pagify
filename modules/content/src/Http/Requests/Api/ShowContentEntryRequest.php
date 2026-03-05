<?php

namespace Pagify\Content\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ShowContentEntryRequest extends FormRequest
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
        return [];
    }
}
