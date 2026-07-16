<?php

declare(strict_types=1);

/**
 * Application bootstrap: autoloading, environment, error handling, session.
 * Returns the fully-configured Router ready to dispatch.
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

define('BASE_PATH', dirname(__DIR__));

// ---- Autoloading --------------------------------------------------------
// Prefer Composer's autoloader (also loads Dompdf/PHPMailer/phpdotenv);
// otherwise register a minimal PSR-4 autoloader so the app still boots.
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        if (!str_starts_with($class, 'App\\')) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, 4));
        $file = BASE_PATH . '/app/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
    require BASE_PATH . '/app/Helpers/functions.php';
}

// ---- Environment + config ----------------------------------------------
Env::load(BASE_PATH . '/.env');
$config = require BASE_PATH . '/config/config.php';

// ---- Error handling -----------------------------------------------------
$debug = (bool) $config['app']['debug'];
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e) use ($debug): void {
    Logger::error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    if ($debug) {
        http_response_code(500);
        echo '<pre style="padding:1rem;font-family:monospace;">';
        echo 'Exception: ' . htmlspecialchars($e->getMessage()) . "\n";
        echo htmlspecialchars($e->getFile() . ':' . $e->getLine()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
        exit;
    }
    Response::serverError();
});

// ---- Session + security headers ----------------------------------------
Session::start($config['session']);
Response::sendSecurityHeaders($debug);

// ---- Shared view data ---------------------------------------------------
View::share('appName', $config['app']['name']);
View::share('appConfig', $config);
View::share('currentUser', Auth::user());
View::share('csrf', Csrf::token());

// ---- Routes -------------------------------------------------------------
$router = new Router();
require BASE_PATH . '/routes/web.php';

return $router;
