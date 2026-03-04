<?php

namespace Modules\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocaleRequest extends FormRequest
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
        $supported = config('core.locales.supported', ['en']);

        return [
            'locale' => ['required', 'string', 'in:' . implode(',', $supported)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return trans('core::validation.messages');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('core::validation.attributes');
    }
}
