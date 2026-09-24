<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with view rendering + redirect helpers.
 */
abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        View::render($template, $data);
    }

    /**
     * Whether the current request wants the "embed" experience (a form loaded
     * inside a modal iframe). Read from an `embed=1` query/post param.
     */
    protected function wantsEmbed(Request $request): bool
    {
        return (string) $request->input('embed', '') === '1';
    }

    /**
     * End an embed flow: tell the parent window to close the modal and refresh.
     */
    protected function embedDone(string $message = 'Saved'): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        $msg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui,sans-serif;padding:20px;color:#374151;font-size:14px">'
            . $msg . '…'
            . '<script>try{parent.postMessage({habitract:"saved"},"*");}catch(e){}</script>';
        exit;
    }

    protected function redirect(string $to): never
    {
        Response::redirect($to);
    }

    protected function back(string $fallback = '/'): never
    {
        $to = $_SERVER['HTTP_REFERER'] ?? $fallback;
        Response::redirect($to);
    }

    protected function withErrors(array $errors, array $input = []): never
    {
        Session::flashErrors($errors);
        Session::flashInput($input);
        $this->back();
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Guard that the given association id matches the current user's tenant.
     * Super admins bypass. Used as a defence-in-depth check inside actions.
     */
    protected function authorizeAssociation(?int $associationId): void
    {
        if (Auth::isSuperAdmin()) {
            return;
        }
        $current = Auth::associationId();
        if ($associationId === null || $current === null || $associationId !== $current) {
            Response::forbidden();
        }
    }

    /**
     * Require the current user to hold one of the given roles.
     */
    protected function requireRole(string ...$roles): void
    {
        if (!Auth::is(...$roles)) {
            Response::forbidden();
        }
    }

    protected function config(?string $key = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
        }
        if ($key === null) {
            return $config;
        }
        $value = $config;
        foreach (explode('.', $key) as $segment) {
            $value = $value[$segment] ?? null;
        }
        return $value;
    }
}
