<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Requires an authenticated user. Also enforces the "must change password"
 * and subscription-expiry gates.
 */
final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect('/login');
        }

        // Force password change before allowing anything else.
        if (Auth::mustChangePassword() && !$this->isPasswordChangeRoute($request)) {
            Response::redirect('/password/force-change');
        }
    }

    private function isPasswordChangeRoute(Request $request): bool
    {
        return in_array($request->path(), ['/password/force-change', '/logout'], true);
    }
}
