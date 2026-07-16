<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

/**
 * Simple regex router with per-route middleware metadata (auth + roles).
 */
final class Router
{
    /** @var list<array{method:string,pattern:string,regex:string,params:list<string>,handler:array,options:array}> */
    private array $routes = [];

    private array $groupStack = [];

    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    public function put(string $path, array $handler, array $options = []): void
    {
        $this->add('PUT', $path, $handler, $options);
    }

    public function delete(string $path, array $handler, array $options = []): void
    {
        $this->add('DELETE', $path, $handler, $options);
    }

    /**
     * Group routes under shared options (e.g. auth + roles).
     */
    public function group(array $options, callable $callback): void
    {
        $this->groupStack[] = $options;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, array $handler, array $options): void
    {
        foreach ($this->groupStack as $group) {
            $options = $this->mergeOptions($group, $options);
        }

        $params = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method'  => $method,
            'pattern' => $path,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    private function mergeOptions(array $base, array $override): array
    {
        $merged = array_merge($base, $override);
        if (isset($base['roles']) || isset($override['roles'])) {
            $merged['roles'] = $override['roles'] ?? $base['roles'];
        }
        return $merged;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['params'], $matches) ?: [];

            $this->runMiddleware($request, $route['options']);
            $this->invoke($route['handler'], $request, $params);
            return;
        }

        // No route matched — differentiate 404 vs 405 lightly.
        Response::notFound();
    }

    private function runMiddleware(Request $request, array $options): void
    {
        // CSRF check on all state-changing requests.
        if ($request->isPost()) {
            (new CsrfMiddleware())->handle($request);
        }

        if (!empty($options['auth'])) {
            (new AuthMiddleware())->handle($request);
        }

        if (!empty($options['roles'])) {
            (new RoleMiddleware((array) $options['roles']))->handle($request);
        }
    }

    private function invoke(array $handler, Request $request, array $params): void
    {
        [$class, $action] = $handler;
        if (!class_exists($class)) {
            Logger::error("Controller not found: {$class}");
            Response::serverError();
        }
        $controller = new $class();
        if (!method_exists($controller, $action)) {
            Logger::error("Action not found: {$class}::{$action}");
            Response::serverError();
        }
        $controller->$action($request, $params);
    }
}
