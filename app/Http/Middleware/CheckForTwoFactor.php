<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Auth;
use Closure;

class CheckForTwoFactor
{
    /**
     * Routes to ignore for Two Factor Auth
     */
    const IGNORE_ROUTES = ['two-factor', 'two-factor-enroll', 'setup', 'logout'];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Skip the logic if the user is on the two factor pages or the setup pages
        if (in_array($request->route()->getName(), self::IGNORE_ROUTES)) {
            return $next($request);
        }

        // Two-factor is enabled (either optional or required)
        if ($settings = Setting::getSettings()) {
            if (Auth::check() && ($settings->two_factor_enabled != '')) {
                // This user is already 2fa-authed
                if ($request->session()->get('2fa_authed')==Auth::user()->id) {
                    return $next($request);
                }

                // Two-factor is optional and the user has NOT opted in, let them through
                if (($settings->two_factor_enabled == '1') && (Auth::user()->two_factor_optin != '1')) {
                    return $next($request);
                }

                // Otherwise make sure they're enrolled and show them the 2FA code screen
                if ((Auth::user()->two_factor_secret != '') && (Auth::user()->two_factor_enrolled == '1')) {
                    return redirect()->route('two-factor')->with('info', 'Please enter your two-factor authentication code.');
                }

                return redirect()->route('two-factor-enroll')->with('success', 'Please enroll a device in two-factor authentication.');
            }
        }

        return $next($request);
    }
}
