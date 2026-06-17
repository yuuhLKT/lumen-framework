<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\HttpStatus;

final class Router
{
    /**
     * @var array<string, array<int, array{path: string, handler: callable|array{0: class-string, 1: string}|string, auth: bool}>>
     */
    private array $routes = [];

    /** @var array{method: string, index: int}|null */
    private ?array $lastRoute = null;

    /** @param callable|array{0: class-string, 1: string}|string $handler */
    public function get(string $path, callable|array|string $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    /** @param callable|array{0: class-string, 1: string}|string $handler */
    public function post(string $path, callable|array|string $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    /** @param callable|array{0: class-string, 1: string}|string $handler */
    public function put(string $path, callable|array|string $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    /** @param callable|array{0: class-string, 1: string}|string $handler */
    public function patch(string $path, callable|array|string $handler): self
    {
        return $this->add('PATCH', $path, $handler);
    }

    /** @param callable|array{0: class-string, 1: string}|string $handler */
    public function delete(string $path, callable|array|string $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes[$request->method()] ?? [] as $route) {
            $params = $this->match($route['path'], $request->path());

            if ($params === null) {
                continue;
            }

            if ($route['auth'] && !Auth::check($request)) {
                return Response::json(['error' => 'Token de autenticação inválido ou ausente.'], HttpStatus::UNAUTHORIZED);
            }

            return $this->runHandler($route['handler'], $request, $params);
        }

        if ($this->pathExistsForAnotherMethod($request)) {
            return Response::json(['error' => 'Método não permitido'], HttpStatus::METHOD_NOT_ALLOWED);
        }

        return Response::json(['error' => 'Rota não encontrada'], HttpStatus::NOT_FOUND);
    }

    /**
     * @param callable|array{0: class-string, 1: string}|string $handler
     */
    private function add(string $method, string $path, callable|array|string $handler): self
    {
        $this->routes[$method][] = [
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'auth' => false,
        ];

        $this->lastRoute = [
            'method' => $method,
            'index' => array_key_last($this->routes[$method]),
        ];

        return $this;
    }

    public function auth(): self
    {
        if ($this->lastRoute === null) {
            return $this;
        }

        $this->routes[$this->lastRoute['method']][$this->lastRoute['index']]['auth'] = true;

        return $this;
    }

    /** @return array<string, string>|null */
    private function match(string $routePath, string $requestPath): ?array
    {
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $matches) use (&$paramNames): string {
            $paramNames[] = $matches[1];

            return '([^/]+)';
        }, $routePath);

        if ($pattern === null || !preg_match('#^' . $pattern . '$#', $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);

        return array_combine($paramNames, array_map('urldecode', $matches)) ?: [];
    }

    /**
     * @param callable|array{0: class-string, 1: string}|string $handler
     * @param array<string, string> $params
     */
    private function runHandler(callable|array|string $handler, Request $request, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $handler = [new $class(), $method];
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $handler = [new $class(), $method];
        }

        if (!is_callable($handler)) {
            return Response::json(['error' => 'Handler inválido.'], HttpStatus::INTERNAL_SERVER_ERROR);
        }

        $result = call_user_func($handler, $request, $params);

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function pathExistsForAnotherMethod(Request $request): bool
    {
        foreach ($this->routes as $method => $routes) {
            if ($method === $request->method()) {
                continue;
            }

            foreach ($routes as $route) {
                if ($this->match($route['path'], $request->path()) !== null) {
                    return true;
                }
            }
        }

        return false;
    }
}
