<?php

declare(strict_types=1);

namespace App\Core;

use AltoRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Core\Exceptions\RouteNotFoundException;
use App\Core\Exceptions\ControllerNotFoundException;

class Route
{
    private static ?AltoRouter $router = null;
    private static ?string $currentRoute = null;

    /** @var array<string, array<int, string>> */
    private static array $middlewares = [];

    private static function getRouter(): AltoRouter
    {
        if (self::$router === null) {
            self::$router = new AltoRouter();
        }
        return self::$router;
    }

    /**
     * Clears all registered routes and middleware. Intended for test isolation.
     */
    public static function reset(): void
    {
        self::$router = null;
        self::$currentRoute = null;
        self::$middlewares = [];
    }

    public static function get(string $uri, string $controller, ?string $name = null): static
    {
        return self::map('GET', $uri, $controller, $name);
    }

    public static function post(string $uri, string $controller, ?string $name = null): static
    {
        return self::map('POST', $uri, $controller, $name);
    }

    public static function put(string $uri, string $controller, ?string $name = null): static
    {
        return self::map('PUT', $uri, $controller, $name);
    }

    public static function delete(string $uri, string $controller, ?string $name = null): static
    {
        return self::map('DELETE', $uri, $controller, $name);
    }

    public static function patch(string $uri, string $controller, ?string $name = null): static
    {
        return self::map('PATCH', $uri, $controller, $name);
    }

    private static function map(string $method, string $uri, string $controller, ?string $name): static
    {
        $name = $name ?? $uri;
        self::getRouter()->map($method, $uri, $controller, $name);
        self::$currentRoute = $name;
        return new static;
    }

    public static function dispatch(?string $requestUri = null, ?string $requestMethod = null): void
    {
        $uri = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $method = $requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = parse_url($uri, PHP_URL_QUERY) ?? '';
        $target = self::canonicalTarget($path, $query);
        if ($target !== null) {
            if (in_array($method, ['GET', 'HEAD'], true)) {
                self::sendRedirect($target);
                return;
            }
            $uri = $target;
        }

        $match = self::getRouter()->match($uri, $method);
        if ($match === false) {
            throw new RouteNotFoundException("No route matched {$method} {$uri}");
        }

        self::$currentRoute = $match['name'];

        $names = self::$middlewares[$match['name']] ?? [];
        if ($names !== []) {
            $runner = new MiddlewareRunner();
            if (!$runner->run($names)) {
                $runner->failed?->handleFailure();
                exit;
            }
        }

        [$controllerPath, $controllerMethod] = explode('#', $match['target']);
        $controller = 'App\\Controllers\\' . str_replace('/', '\\', $controllerPath);
        if (!class_exists($controller) || !method_exists($controller, $controllerMethod)) {
            throw new ControllerNotFoundException("Invalid controller or method: {$match['target']}");
        }

        $request = Request::createFromGlobals();
        $generated = self::getRouter()->generate($match['name']);
        $controllerInstance = new $controller($request, $generated);
        $controllerInstance->currentRoute = self::replacePlaceholders($match['params']);
        $arguments = self::coerceArguments($controller, $controllerMethod, array_values($match['params']));
        $result = $controllerInstance->$controllerMethod(...$arguments);

        // View controllers echo and return void; API controllers return a
        // Response that the router is responsible for sending.
        if ($result instanceof Response) {
            $result->send();
        }
    }

    /**
     * Returns the canonical URL (with trailing slash) when $path should be
     * redirected, or null when it is already canonical or file-like.
     */
    public static function canonicalTarget(string $path, string $query = ''): ?string
    {
        if ($path === '/' || str_ends_with($path, '/') || preg_match('/\.[a-zA-Z0-9]+$/', basename($path)) === 1) {
            return null;
        }

        return $path . '/' . ($query !== '' ? '?' . $query : '');
    }

    private static function sendRedirect(string $target): void
    {
        header('Location: ' . $target, true, 301);
        exit;
    }

    public function middleware(string ...$middlewares): static
    {
        $route = self::$currentRoute;
        foreach ($middlewares as $middleware) {
            self::$middlewares[$route][] = $middleware;
        }
        return $this;
    }

    /**
     * @param array<string, string> $params
     */
    /**
     * Router params always arrive as strings; cast each argument to the
     * scalar type declared on the target method so strict_types signatures
     * (e.g. int $id) receive the right type.
     *
     * @param  list<string>  $arguments
     * @return list<mixed>
     */
    private static function coerceArguments(string $controller, string $method, array $arguments): array
    {
        $parameters = (new \ReflectionMethod($controller, $method))->getParameters();

        foreach ($arguments as $index => $value) {
            $type = ($parameters[$index] ?? null)?->getType();
            if (!$type instanceof \ReflectionNamedType || !$type->isBuiltin()) {
                continue;
            }

            $arguments[$index] = match ($type->getName()) {
                'int'   => (int) $value,
                'float' => (float) $value,
                'bool'  => (bool) $value,
                default => $value,
            };
        }

        return $arguments;
    }

    private static function replacePlaceholders(array $params): string
    {
        $route = self::$currentRoute;
        foreach ($params as $key => $value) {
            $route = str_replace(['[i:' . $key . ']', '[*:' . $key . ']'], $value, $route);
        }
        return $route;
    }

    public static function getUrlFromName(string $name, array $params = []): string
    {
        return self::getRouter()->generate($name, $params);
    }
}
