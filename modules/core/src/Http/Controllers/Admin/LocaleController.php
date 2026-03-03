<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Requests\Admin\UpdateLocaleRequest;

class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $admin = $request->user('web');

        if ($admin !== null) {
            $admin->forceFill([
                'locale' => $payload['locale'],
            ])->save();
        }

        app()->setLocale($payload['locale']);
        $request->session()->put('locale', $payload['locale']);

        return back();
    }
}
