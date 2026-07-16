<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Helpers for emitting responses (redirects, headers, downloads).
 */
final class Response
{
    public static function redirect(string $to, int $status = 302): never
    {
        header('Location: ' . $to, true, $status);
        exit;
    }

    public static function sendSecurityHeaders(bool $debug): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 0');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Content Security Policy. 'unsafe-inline' is required for the small
        // amount of inline Alpine/Tailwind bootstrap; scripts are otherwise
        // self-hosted.
        $csp = "default-src 'self'; "
            . "img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self' 'unsafe-inline'; "
            . "font-src 'self' data:; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'";
        header('Content-Security-Policy: ' . $csp);
    }

    public static function notFound(string $message = 'Page not found'): never
    {
        http_response_code(404);
        View::renderError(404, $message);
        exit;
    }

    public static function forbidden(string $message = 'You are not allowed to access this page.'): never
    {
        http_response_code(403);
        View::renderError(403, $message);
        exit;
    }

    public static function serverError(string $message = 'Something went wrong.'): never
    {
        http_response_code(500);
        View::renderError(500, $message);
        exit;
    }
}
