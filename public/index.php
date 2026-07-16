<?php

declare(strict_types=1);

/**
 * Front controller — the single public entry point. Everything is routed
 * through here; nothing else in the project is web-accessible.
 */

use App\Core\Request;

$router = require dirname(__DIR__) . '/app/bootstrap.php';

$router->dispatch(new Request());
