<?php

namespace Modules\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminTokenRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:80'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
