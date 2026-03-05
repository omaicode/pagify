<?php

namespace Pagify\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminGroupRequest extends FormRequest
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
        $roleId = (int) $this->route('roleId');

        return [
            'name' => ['required', 'string', 'max:120', 'unique:roles,name,'.$roleId],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
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
