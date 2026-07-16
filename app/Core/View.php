<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP templating. Views live in app/Views and are rendered with an
 * extracted data array; a view may declare a layout via $this->layout().
 */
final class View
{
    private static array $shared = [];

    private ?string $layout = null;
    private array $sections = [];

    /**
     * Share data with every view (e.g. the authenticated user, app config).
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function make(string $template, array $data = []): string
    {
        return (new self())->renderTemplate($template, $data);
    }

    public static function render(string $template, array $data = []): void
    {
        echo self::make($template, $data);
    }

    private function renderTemplate(string $template, array $data): string
    {
        $data = array_merge(self::$shared, $data);
        $content = $this->capture($this->path($template), $data);

        if ($this->layout !== null) {
            $layoutPath = $this->path($this->layout);
            $this->sections['content'] = $content;
            $data['__sections'] = $this->sections;
            return $this->capture($layoutPath, $data);
        }

        return $content;
    }

    private function capture(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$file}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private function path(string $template): string
    {
        $template = str_replace('.', '/', $template);
        return dirname(__DIR__) . '/Views/' . $template . '.php';
    }

    // ---- Methods usable from within a template ($this->...) -------------

    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function section(string $name): mixed
    {
        return $this->sections[$name] ?? null;
    }

    public static function renderError(int $code, string $message): void
    {
        $file = dirname(__DIR__) . '/Views/errors/' . $code . '.php';
        if (is_file($file)) {
            echo self::make('errors.' . $code, ['message' => $message, 'code' => $code]);
            return;
        }
        echo self::make('errors.generic', ['message' => $message, 'code' => $code]);
    }
}
