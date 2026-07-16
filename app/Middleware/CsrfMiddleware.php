<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

/**
 * Validates the CSRF token on every state-changing request.
 */
final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (!Csrf::validate($request->csrfToken())) {
            Logger::warning('CSRF token mismatch', [
                'path' => $request->path(),
                'ip'   => $request->ip(),
            ]);
            Response::forbidden('Your session has expired or the form token was invalid. Please try again.');
        }
    }
}
