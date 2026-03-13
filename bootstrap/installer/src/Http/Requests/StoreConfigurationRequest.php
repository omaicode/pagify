<?php

namespace Pagify\Installer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigurationRequest extends FormRequest
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
            'project_name' => ['required', 'string', 'max:255'],
            'app_url' => ['required', 'url', 'max:255'],

            'db.connection' => ['required', 'string', 'in:mysql,pgsql,sqlite'],
            'db.host' => ['nullable', 'string', 'max:190'],
            'db.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'db.database' => ['required', 'string', 'max:190'],
            'db.username' => ['nullable', 'string', 'max:190'],
            'db.password' => ['nullable', 'string', 'max:190'],

            'mail.mailer' => ['required', 'string', 'in:smtp,log,array'],
            'mail.host' => ['nullable', 'string', 'max:190'],
            'mail.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail.username' => ['nullable', 'string', 'max:190'],
            'mail.password' => ['nullable', 'string', 'max:190'],
            'mail.encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'mail.from_address' => ['nullable', 'email', 'max:190'],
            'mail.from_name' => ['nullable', 'string', 'max:190'],

            'admin.name' => ['required', 'string', 'max:190'],
            'admin.username' => ['required', 'string', 'max:190'],
            'admin.email' => ['nullable', 'email', 'max:190'],
            'admin.password' => ['required', 'string', 'min:8', 'max:190'],
        ];
    }
}
