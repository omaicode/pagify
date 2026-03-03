<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supported = config('core.locales.supported', ['en']);

        $payload = $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', $supported)],
        ]);

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
