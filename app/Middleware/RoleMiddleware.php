<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * Requires the authenticated user to hold one of the allowed roles.
 * Authorization is always enforced here (not just by hiding menu items).
 */
final class RoleMiddleware
{
    /** @var list<string> */
    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        if (!Auth::is(...$this->roles)) {
            Response::forbidden();
        }
    }
}
