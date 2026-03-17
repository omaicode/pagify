<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\AuditLogger;

class AuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('core.admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login', [
            'loginAction' => route('core.admin.login.store'),
            'forgotPasswordUrl' => route('core.admin.password.request'),
            'statusMessage' => session('status'),
        ]);
    }

    public function forgotPassword(): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('core.admin.dashboard');
        }

        return Inertia::render('Admin/Auth/ForgotPassword', [
            'submitAction' => route('core.admin.password.email'),
            'loginUrl' => route('core.admin.login'),
            'statusMessage' => session('status'),
        ]);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('admins')->sendResetLink([
            'email' => (string) $credentials['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => __($status),
        ]);
    }

    public function resetPassword(Request $request, string $token): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('core.admin.dashboard');
        }

        return Inertia::render('Admin/Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'submitAction' => route('core.admin.password.update'),
            'loginUrl' => route('core.admin.login'),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('admins')->reset(
            [
                'email' => (string) $payload['email'],
                'password' => (string) $payload['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => (string) $payload['token'],
            ],
            static function (Admin $admin, string $password): void {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('core.admin.login')->with('status', __($status));
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => __($status),
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
