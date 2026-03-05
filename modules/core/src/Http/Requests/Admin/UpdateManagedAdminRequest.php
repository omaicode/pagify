<?php

namespace Pagify\Core\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagedAdminRequest extends FormRequest
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
        $adminId = (int) $this->route('managedAdminId');

        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', 'unique:admins,username,'.$adminId],
            'email' => ['nullable', 'email', 'max:190', 'unique:admins,email,'.$adminId],
            'locale' => ['required', 'string', 'max:8'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
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
