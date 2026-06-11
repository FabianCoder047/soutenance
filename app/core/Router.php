<?php
declare(strict_types=1);

class Router {
    private static array $routes = [];

    public static function get(string $path, string $handler): void {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post(string $path, string $handler): void {
        self::$routes['POST'][$path] = $handler;
    }

    public static function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $basePath = rtrim((string)($_ENV['APP_BASE_PATH'] ?? ''), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        // Normalize path: strip trailing slash unless it's the root path
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        if (!isset(self::$routes[$method][$path])) {
            http_response_code(404);
            $viewsPath = dirname(__DIR__) . '/views/errors/404.php';
            if (file_exists($viewsPath)) {
                include $viewsPath;
            } else {
                echo '<h1>404 Not Found</h1><p>La page recherchée n\'existe pas.</p>';
            }
            exit;
        }

        $handler = self::$routes[$method][$path];
        [$controllerName, $action] = explode('@', $handler);

        $controllerFile = dirname(__DIR__) . '/controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            die("Fichier de contrôleur introuvable : " . Helper::escape($controllerName));
        }
        
        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            die("Classe de contrôleur introuvable : " . Helper::escape($controllerName));
        }

        $controller = new $controllerName();
        if (!method_exists($controller, $action)) {
            die("Action " . Helper::escape($action) . " introuvable dans " . Helper::escape($controllerName));
        }

        $controller->$action();
    }
}
