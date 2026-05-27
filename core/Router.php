<?php
class Router
{
    private array $routes = [];

    public function get(string $path, string $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, string $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path (e.g. /knn-pln/public)
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $uri      = '/' . ltrim(substr($uri, strlen($basePath)), '/');
        $uri      = $uri === '' ? '/' : $uri;

        // Exact match first
        if (isset($this->routes[$method][$uri])) {
            [$controllerName, $methodName] = explode('@', $this->routes[$method][$uri]);
            $controllerFile = APP_PATH . "/Controllers/{$controllerName}.php";
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controller = new $controllerName();
                $controller->$methodName();
                return;
            }
        }

        // Pattern match for routes with {param} placeholders
        foreach ($this->routes[$method] ?? [] as $pattern => $action) {
            $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                [$controllerName, $methodName] = explode('@', $action);
                $controllerFile = APP_PATH . "/Controllers/{$controllerName}.php";
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    $controller = new $controllerName();
                    $controller->$methodName(...$matches);
                    return;
                }
            }
        }

        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
    }
}
