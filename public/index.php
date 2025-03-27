<?php
// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
require_once __DIR__ . '/../src/config/config.php';

/**
 * Cache Manager Class
 * Handles all HTTP caching functionality
 */
class CacheManager {
    // Cache types
    const CACHE_NONE = 'none';         // No caching (auth pages)
    const CACHE_PUBLIC = 'public';     // Public pages (home, blog posts)
    const CACHE_STATIC = 'static';     // Static assets (CSS, JS, images)
    
    /**
     * Set appropriate cache headers based on content type
     */
    public static function setCacheHeaders($cacheType = self::CACHE_NONE) {
        error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> setCacheHeaders");
        // Remove any existing cache headers to prevent conflicts
        header_remove('Pragma');
        header_remove('Cache-Control');
        header_remove('Expires');
        // Default Vary header for all responses
        header('Vary: Accept-Encoding, Cookie');
        
        switch($cacheType) {
            case self::CACHE_STATIC:
                // For static assets - long cache time
                header('Cache-Control: public, max-age=86400, s-maxage=172800'); // 1 day browser, 2 days proxy
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
                break;
                
            case self::CACHE_PUBLIC:
                // For public pages - medium cache time
                header('Cache-Control: public, max-age=3600, s-maxage=7200'); // 1 hour browser, 2 hours proxy
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                
                // Add ETag for validation
                $etag = md5(filemtime($_SERVER['SCRIPT_FILENAME']) . $_SERVER['REQUEST_URI']);
                header('ETag: "' . $etag . '"');
                
                // Process conditional requests
                if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
                    trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
                    header("HTTP/1.1 304 Not Modified");
                    exit;
                }
                break;
                
            case self::CACHE_NONE:
            default:
                // For authenticated or dynamic content - prevent caching
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
                header('Pragma: no-cache');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 1) . ' GMT');
                break;
        }
    }
    
    /**
     * Determine cache type based on route or path
     */
    public static function determineCacheType($route = null) {
        
        error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> determineCacheType");
        // Auth routes should never be cached
        if (strpos($_SERVER['REQUEST_URI'], '/auth/') === 0) {
            return self::CACHE_NONE;
        }
        
        // Static assets should be cached longer
        if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2)$/', $_SERVER['REQUEST_URI'])) {
            return self::CACHE_STATIC;
        }
        
        // If user is logged in, don't cache
        if (isset($_SESSION['user_id'])) {
            return self::CACHE_NONE;
        }
        
        // Public pages can be cached
        $publicRoutes = ['/', '/posts', '/posts/view'];
        foreach ($publicRoutes as $publicRoute) {
            if (strpos($_SERVER['REQUEST_URI'], $publicRoute) === 0) {
                return self::CACHE_PUBLIC;
            }
        }
        
        // Default to no caching
        return self::CACHE_NONE;
    }
}

/**
 * Enhanced Router Class with Cache Control
 */
class Router {
    private static $instance;
    private $routes = [
        'GET' => [],
        'POST' => []
    ];

    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Register a GET route with cache type
    public function get($path, $callback, $cacheType = null) {
        //display pathe of router 
        error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> get: $path");
        $this->routes['GET'][$path] = [
            'callback' => $callback,
            'cacheType' => $cacheType
        ];
    }

    // Register a POST route (POST requests are never cached)
    public function post($path, $callback) {
        $this->routes['POST'][$path] = [
            'callback' => $callback,
            'cacheType' => CacheManager::CACHE_NONE
        ];
    }

    // Resolve the current route
    public function resolve() {
        // display path of resolve : 
        error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> resolve: " . $_SERVER['REQUEST_URI']);
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Check if route exists
        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
            $callback = $route['callback'];
            $cacheType = $route['cacheType'];
            
            // If cache type is specified in route, use it
            // Otherwise determine automatically
            if ($cacheType === null) {
                $cacheType = CacheManager::determineCacheType($uri);
            }
            
            // Set appropriate cache headers
            CacheManager::setCacheHeaders($cacheType);
            
            // Handle controller@method format
            if (is_string($callback) && strpos($callback, '@') !== false) {
                list($controller, $method) = explode('@', $callback);

                // Create controller instance and call method
                $controller = new $controller();
                $controller->$method();
            } else if (is_callable($callback)) {
                // Execute callback function
                call_user_func($callback);
            }

            return true; // Route was found and handled
        }

        // Handle parameterized routes
        foreach ($this->routes[$method] as $route => $routeData) {
            // Skip non-parameterized routes (already checked above)
            if (strpos($route, ':') === false) {
                continue;
            }

            // Convert route pattern to regex
            $pattern = preg_replace('/:([^\/]+)/', '(?<$1>[^/]+)', $route);
            $pattern = "@^" . $pattern . "$@D";

            // Check if current URI matches pattern
            if (preg_match($pattern, $uri, $matches)) {
                // Extract parameters
                $params = array_filter($matches, function ($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);
                
                $callback = $routeData['callback'];
                $cacheType = $routeData['cacheType'];
                
                // If cache type is specified in route, use it
                // Otherwise determine automatically
                if ($cacheType === null) {
                    $cacheType = CacheManager::determineCacheType($uri);
                }
                
                // Set appropriate cache headers
                CacheManager::setCacheHeaders($cacheType);

                // Handle controller@method format
                if (is_string($callback) && strpos($callback, '@') !== false) {
                    list($controller, $method) = explode('@', $callback);

                    // Create controller instance and call method with parameters
                    $controller = new $controller();
                    call_user_func_array([$controller, $method], $params);
                } else if (is_callable($callback)) {
                    // Execute callback function with parameters
                    call_user_func_array($callback, $params);
                }

                return true; // Route was found and handled
            }
        }

        // No route was found - set cache headers to no-cache
        CacheManager::setCacheHeaders(CacheManager::CACHE_NONE);
        
        return false; // No route was found
    }
}

// Autoload classes (simple autoloader)
spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $baseDirs = [
        __DIR__ . '/../src/controllers/',
        __DIR__ . '/../src/models/',
        __DIR__ . '/../src/lib/'
    ];

    foreach ($baseDirs as $baseDir) {
        $file = $baseDir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});

// Initialize router
$router = Router::getInstance();



// Define routes with cache types
// Auth routes - no caching
$router->get('/auth/login', 'AuthController@showLoginForm', CacheManager::CACHE_NONE);
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/register', 'AuthController@showRegisterForm', CacheManager::CACHE_NONE);
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/logout', 'AuthController@logout', CacheManager::CACHE_NONE);
$router->get('/auth/reset-password', 'AuthController@showPasswordResetRequestForm', CacheManager::CACHE_NONE);
$router->post('/auth/reset-password', 'AuthController@processPasswordResetRequest');
$router->get('/auth/reset-password/confirm', 'AuthController@showPasswordResetForm', CacheManager::CACHE_NONE);
$router->post('/auth/reset-password/confirm', 'AuthController@processPasswordReset');

// Profile routes - no caching (user-specific)
$router->get('/profile', 'ProfileController@show', CacheManager::CACHE_NONE);
$router->get('/profile/edit', 'ProfileController@edit', CacheManager::CACHE_NONE);
$router->post('/profile/update', 'ProfileController@update');
$router->get('/profile/change-password', 'ProfileController@showChangePasswordForm', CacheManager::CACHE_NONE);
$router->post('/profile/change-password', 'ProfileController@changePassword');

// Public content - can be cached
$router->get('/', 'PostController@index', CacheManager::CACHE_PUBLIC);
$router->get('/posts', 'PostController@index', CacheManager::CACHE_PUBLIC);
$router->get('/posts/view/:id', 'PostController@show', CacheManager::CACHE_PUBLIC);

// Content creation/editing - no caching
$router->get('/posts/create', 'PostController@create', CacheManager::CACHE_NONE);
$router->post('/posts/create', 'PostController@store');
$router->get('/posts/edit/:id', 'PostController@edit', CacheManager::CACHE_NONE);
$router->post('/posts/edit/:id', 'PostController@update');
$router->get('/posts/delete/:id', 'PostController@delete', CacheManager::CACHE_NONE);

// Video routes - same pattern as posts
$router->get('/videos', 'VideoController@index', CacheManager::CACHE_PUBLIC);
$router->get('/videos/view/:id', 'VideoController@show', CacheManager::CACHE_PUBLIC);
$router->get('/videos/create', 'VideoController@create', CacheManager::CACHE_NONE);
$router->post('/videos/create', 'VideoController@store');
$router->get('/videos/edit/:id', 'VideoController@edit', CacheManager::CACHE_NONE);
$router->post('/videos/edit/:id', 'VideoController@update');
$router->get('/videos/delete/:id', 'VideoController@delete', CacheManager::CACHE_NONE);

// // Media routes - with appropriate cache settings
// $router->get('/uploads/images/:file', 'MediaController@serve', CacheManager::CACHE_STATIC);
// $router->get('/uploads/thumbnails/:file', 'MediaController@serve', CacheManager::CACHE_STATIC);
// $router->get('/uploads/videos/:file', 'MediaController@serve', CacheManager::CACHE_STATIC);

// URL: /uploads/images/filename.jpg
$router->get('/uploads/:folder/:filename', 'MediaController@serve', CacheManager::CACHE_NONE);

// Try to resolve the current route
$routeWasResolved = $router->resolve();

// Only output the 404 page if no route was found
if (!$routeWasResolved) {
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Page Not Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5 text-center">
            <h1 class="display-1">404</h1>
            <h2>Page Not Found</h2>
            <p>The page you requested could not be found.</p>
            <a href="/" class="btn btn-primary">Go to Homepage</a>
        </div>
    </body>
    </html>';
}