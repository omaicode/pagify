<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Core\Services\AuditLogger;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('core.admin.dashboard');
        }

        return view('core::admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials, (bool) $request->boolean('remember'))) {
            $request->session()->regenerate();

            app(AuditLogger::class)->log(
                action: 'auth.login',
                entityType: 'admin',
                entityId: (string) Auth::guard('web')->id(),
                metadata: [
                    'username' => $credentials['username'],
                ],
            );

            return redirect()->intended(route('core.admin.dashboard'));
        }

        return back()->withErrors([
            'username' => __('The provided credentials are invalid.'),
        ])->onlyInput('username');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $adminId = Auth::guard('web')->id();

        if ($adminId !== null) {
            app(AuditLogger::class)->log(
                action: 'auth.logout',
                entityType: 'admin',
                entityId: (string) $adminId,
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('core.admin.login');
    }
}
