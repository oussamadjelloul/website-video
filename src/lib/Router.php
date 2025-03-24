<?php
class Router
{
    private $routes = [];
    private static $instance = null;

    // Singleton pattern
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Add a route
    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
        return $this;
    }

    // Shorthand for GET route
    public function get($path, $handler)
    {
        return $this->add('GET', $path, $handler);
    }

    // Shorthand for POST route
    public function post($path, $handler)
    {
        return $this->add('POST', $path, $handler);
    }

    // Match the current request to a route and execute handler
    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');

        if (empty($uri)) {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route to regex pattern
            $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // Remove numeric keys
                foreach ($matches as $key => $match) {
                    if (is_int($key)) {
                        unset($matches[$key]);
                    }
                }

                // Execute the handler
                if (is_callable($route['handler'])) {
                    return call_user_func($route['handler'], $matches);
                } else if (is_string($route['handler']) && strpos($route['handler'], '@') !== false) {
                    list($controller, $method) = explode('@', $route['handler']);
                    if (class_exists($controller)) {
                        $controllerInstance = new $controller();
                        if (method_exists($controllerInstance, $method)) {
                            return call_user_func_array([$controllerInstance, $method], [$matches]);
                        }
                    }
                }
            }
        }

        // No route matched
        header('HTTP/1.1 404 Not Found');
        echo '404 Not Found';
        exit;
    }
}
