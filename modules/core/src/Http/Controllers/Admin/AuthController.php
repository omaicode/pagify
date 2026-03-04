<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\AuditLogger;

class AuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('core.admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login', [
            'loginAction' => route('core.admin.login.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim((string) $credentials['username']);
        $password = (string) $credentials['password'];
        $remember = (bool) $request->boolean('remember');

        $attemptCredentials = [
            [
                'username' => $identifier,
                'password' => $password,
            ],
            [
                'email' => $identifier,
                'password' => $password,
            ],
        ];

        $authenticated = false;

        foreach ($attemptCredentials as $attempt) {
            if (Auth::guard('web')->attempt($attempt, $remember)) {
                $authenticated = true;
                break;
            }
        }

        if ($authenticated) {
            $request->session()->regenerate();

            app(AuditLogger::class)->log(
                action: 'auth.login',
                entityType: 'admin',
                entityId: (string) Auth::guard('web')->id(),
                metadata: [
                    'identifier' => $identifier,
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
