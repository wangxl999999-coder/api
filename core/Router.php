<?php

namespace Core;

class Router
{
    private $routes = [];
    private $middlewares = [];

    public function get($path, $handler, $middlewares = [])
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post($path, $handler, $middlewares = [])
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put($path, $handler, $middlewares = [])
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete($path, $handler, $middlewares = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function any($path, $handler, $middlewares = [])
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler, $middlewares);
        }
    }

    public function group($prefix, $callback, $middlewares = [])
    {
        $originalRoutes = $this->routes;
        $this->routes = [];
        
        $callback($this);
        
        foreach ($this->routes as $route) {
            $route['path'] = $prefix . $route['path'];
            $route['middlewares'] = array_merge($middlewares, $route['middlewares']);
            $originalRoutes[] = $route;
        }
        
        $this->routes = $originalRoutes;
    }

    private function addRoute($method, $path, $handler, $middlewares = [])
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = rtrim($path, '/');
            $path = $path === '' ? '/' : $path;

            $scriptName = dirname($_SERVER['SCRIPT_NAME']);
            if ($scriptName !== '/' && $scriptName !== '\\') {
                $path = preg_replace('#^' . preg_quote($scriptName, '#') . '#', '', $path);
                $path = rtrim($path, '/');
                $path = $path === '' ? '/' : $path;
            }

            if (strpos($path, '/public') === 0) {
                $path = substr($path, 7);
                $path = $path === '' ? '/' : $path;
            }

            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }

                $pattern = $this->convertPathToPattern($route['path']);
                if (preg_match($pattern, $path, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    
                    foreach ($route['middlewares'] as $middleware) {
                        $middlewareClass = "App\\Middlewares\\" . $middleware;
                        if (class_exists($middlewareClass)) {
                            $middlewareInstance = new $middlewareClass();
                            $result = $middlewareInstance->handle();
                            if ($result !== true) {
                                return $result;
                            }
                        }
                    }

                    return $this->executeHandler($route['handler'], $params);
                }
            }

            return $this->errorResponse(404, '接口不存在: ' . $method . ' ' . $path);
        } catch (\Exception $e) {
            $debugInfo = [];
            $config = require __DIR__ . '/../config/config.php';
            $isDebug = $config['app']['debug'] ?? false;
            
            if ($isDebug) {
                $debugInfo = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ];
            }
            
            return $this->errorResponse(500, '服务器内部错误' . ($isDebug ? ': ' . $e->getMessage() : ''), $debugInfo);
        }
    }

    private function convertPathToPattern($path)
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function executeHandler($handler, $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            $controllerClass = "App\\Controllers\\" . $controller;
            
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array([$controllerInstance, $method], $params);
                }
            }
        }

        return $this->errorResponse(500, 'Internal Server Error');
    }

    public function jsonResponse($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function successResponse($data = [], $message = '操作成功', $code = 200)
    {
        return $this->jsonResponse([
            'code' => 200,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function errorResponse($code = 400, $message = '操作失败', $data = [])
    {
        return $this->jsonResponse([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
