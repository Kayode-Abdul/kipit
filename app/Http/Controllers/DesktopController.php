<?php

namespace App\Http\Controllers;

use App\Helpers\DesktopHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesktopController extends Controller
{
    /**
     * Show the desktop splash screen.
     */
    public function splash()
    {
        // Determine where to redirect after splash
        if (DesktopHelper::isFirstLaunch() || !DesktopHelper::isSetupComplete()) {
            $redirectTo = route('desktop.welcome');
        } else {
            $redirectTo = Auth::guard('web')->check() ? '/member' : route('desktop.login');
        }

        return view('desktop.splash', compact('redirectTo'));
    }

    /**
     * Show the welcome carousel (first launch only).
     */
    public function welcome()
    {
        if (DesktopHelper::isSetupComplete()) {
            return redirect()->route('desktop.login');
        }

        return view('desktop.welcome');
    }

    /**
     * Show the native desktop login screen.
     */
    public function login()
    {
        if (Auth::guard('web')->check()) {
            return redirect('/member');
        }

        $isFirstLogin = !DesktopHelper::isFirstLoginComplete();

        return view('desktop.login', compact('isFirstLogin'));
    }

    /**
     * Handle local desktop login.
     * On the very first login after installation, only admin/owner accounts are allowed.
     */
    public function loginSubmit(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('web')->user();

            // Enforce admin-first login on fresh installations
            if (!DesktopHelper::isFirstLoginComplete()) {
                $isAdmin = $user->is_owner
                    || $user->hasRole(\App\Constants\Role::admin);

                if (!$isAdmin) {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'The first login must be from a business owner or admin account. Please contact your administrator.',
                    ])->onlyInput('email');
                }

                // Admin verified — mark first login as complete
                DesktopHelper::markFirstLoginComplete();

                // Refresh the integrity checksum after first admin login
                try {
                    app(\App\Services\DatabaseIntegrityService::class)->refreshChecksum();
                } catch (\Throwable $e) {
                    // Non-critical — log and continue
                    \Illuminate\Support\Facades\Log::warning('Failed to refresh integrity checksum after first login: ' . $e->getMessage());
                }
            }

            // Mirror web TenantLogin: ensure a profile row exists for settings UI.
            try {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->getKey()],
                    ['timezone' => $user->profile?->timezone ?? 'Africa/Lagos'],
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Failed to bootstrap desktop user profile: '.$e->getMessage()
                );
            }

            $request->session()->regenerate();

            return redirect()->intended('/member');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
