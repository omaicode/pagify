<?php

namespace Pagify\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
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
        $permissionId = (int) $this->route('permissionId');

        return [
            'name' => ['required', 'string', 'max:120', 'unique:permissions,name,'.$permissionId],
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
