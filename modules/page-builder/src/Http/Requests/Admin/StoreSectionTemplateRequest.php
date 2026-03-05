<?php

namespace Pagify\PageBuilder\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionTemplateRequest extends FormRequest
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
			'name' => ['required', 'string', 'max:160'],
			'slug' => ['required', 'string', 'alpha_dash', 'max:160'],
			'schema' => ['required', 'array'],
		];
	}
}
