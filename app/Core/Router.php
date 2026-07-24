<?php

namespace App\Core;

use App\Helpers\ResponseHelper;
use Throwable;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): void
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\-]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => $path,
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                
                // Run route middleware pipeline
                foreach ($route['middlewares'] as $middleware) {
                    if (is_callable($middleware)) {
                        call_user_func($middleware);
                    } elseif (is_string($middleware) && class_exists($middleware) && method_exists($middleware, 'handle')) {
                        call_user_func([$middleware, 'handle']);
                    }
                }

                // Filter named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                try {
                    $handler = $route['handler'];
                    if (is_callable($handler)) {
                        call_user_func_array($handler, [$request, $params]);
                    } elseif (is_array($handler) && count($handler) === 2) {
                        list($controllerClass, $methodName) = $handler;
                        if (!class_exists($controllerClass)) {
                            ResponseHelper::serverError("Controller {$controllerClass} not found.");
                        }
                        $controller = new $controllerClass();
                        if (!method_exists($controller, $methodName)) {
                            ResponseHelper::serverError("Method {$methodName} not found in {$controllerClass}.");
                        }
                        call_user_func_array([$controller, $methodName], [$request, $params]);
                    }
                    return;
                } catch (Throwable $e) {
                    error_log("Route Dispatch Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    $debug = $_ENV['APP_DEBUG'] ?? false;
                    $errorMsg = $debug ? $e->getMessage() : "An unexpected server error occurred.";
                    ResponseHelper::serverError($errorMsg);
                }
            }
        }

        // No matching route found
        ResponseHelper::notFound("Endpoint {$method} {$path} not found.");
    }
}
