<?php
/**
 * Core PHP Application Router
 * 
 * Supports standard HTTP verbs, parameter matching ({id}),
 * middleware pipelines, route grouping, and JSON API dispatching.
 */

declare(strict_types=1);

namespace App\Core;

use Exception;

class Router {
    private static array $routes = [];
    private static array $groupMiddleware = [];
    private static string $groupPrefix = '';

    public static function get(string $path, array|callable $handler, array $middleware = []): void {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, array|callable $handler, array $middleware = []): void {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    public static function put(string $path, array|callable $handler, array $middleware = []): void {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    public static function delete(string $path, array|callable $handler, array $middleware = []): void {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    public static function group(array $attributes, callable $callback): void {
        $previousPrefix = self::$groupPrefix;
        $previousMiddleware = self::$groupMiddleware;

        if (isset($attributes['prefix'])) {
            self::$groupPrefix = rtrim(self::$groupPrefix, '/') . '/' . trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $middlewares = is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']];
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $middlewares);
        }

        $callback();

        self::$groupPrefix = $previousPrefix;
        self::$groupMiddleware = $previousMiddleware;
    }

    private static function addRoute(string $method, string $path, array|callable $handler, array $middleware = []): void {
        $fullPath = rtrim(self::$groupPrefix, '/') . '/' . ltrim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        if ($fullPath === '//') {
            $fullPath = '/';
        }

        $mergedMiddleware = array_merge(self::$groupMiddleware, $middleware);

        self::$routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $mergedMiddleware,
        ];
    }

    public static function dispatch(Request $request): void {
        $method = $request->getMethod();
        $uri = $request->getUri();

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = self::buildPattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run Middleware pipeline
                foreach ($route['middleware'] as $mw) {
                    $mwInstance = is_string($mw) ? new $mw() : $mw;
                    $handled = $mwInstance->handle($request);
                    if ($handled === false) {
                        return; // Middleware aborted the request
                    }
                }

                // Execute Handler
                self::invokeHandler($route['handler'], $request, $params);
                return;
            }
        }

        // No route matched: 404
        self::handleNotFound($request);
    }

    private static function buildPattern(string $path): string {
        $escaped = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $escaped . '$#';
    }

    private static function invokeHandler(array|callable $handler, Request $request, array $params): void {
        if (is_callable($handler)) {
            call_user_func($handler, $request, ...array_values($params));
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = is_object($class) ? $class : new $class();
            call_user_func([$controller, $method], $request, ...array_values($params));
            return;
        }

        throw new Exception("Invalid route handler supplied.");
    }

    private static function handleNotFound(Request $request): void {
        $response = new Response();
        $response->setStatusCode(404);

        if ($request->isAjax()) {
            $response->json([
                'success' => false,
                'error' => 'Endpoint not found',
                'path' => $request->getUri(),
            ], 404);
            return;
        }

        try {
            $html = View::render('errors/404', [
                'title' => 'Page Not Found',
                'path' => $request->getUri(),
            ]);
            $response->setContent($html)->send();
        } catch (Exception) {
            $response->setContent("<h1>404 Not Found</h1><p>The requested page does not exist.</p>")->send();
        }
    }
}
