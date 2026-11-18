<?php
/**
 * doodle - Simple front-controller router
 *
 * URL format: index.php?r=Controller/method/params...
 * Or with mod_rewrite: /Controller/method/params...
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Router
{
    public function dispatch(): void
    {
        $parts = $this->parseUrl();
        $target = $parts === null ? null : self::resolve(implode('/', $parts));
        if ($target === null) {
            $this->notFound();
            return;
        }
        [$ctrlClass, $method, $params] = $target;
        $_GET['r'] = implode('/', $parts);
        $controllerObj = new $ctrlClass();

        // Verify CSRF on POST/PUT/DELETE
        if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'DELETE'])) {
            if (!CSRF::verify()) {
                http_response_code(419);
                die('CSRF token mismatch. Please go back, refresh the page, and try again.');
            }
        }

        call_user_func_array([$controllerObj, $method], $params);
    }

    /** Resolve without running constructors/actions; also used by the route audit. */
    public static function resolve(string $route): ?array
    {
        $parts = explode('/', trim($route, '/'));
        if ($parts === ['admin', 'backups']) $parts = ['backup'];
        $name = array_shift($parts) ?: 'home';
        $aliases = ['flashcards'=>'Flashcard', 'messages'=>'Message', 'certificates'=>'Certificate',
            'learning_paths'=>'LearningPath', 'learningpath'=>'LearningPath', 'ai'=>'AI', 'oauth'=>'OAuth'];
        if (!preg_match('/^[A-Za-z]+$/', $name) && !isset($aliases[$name])) return null;
        $controller = $aliases[strtolower($name)] ?? ucfirst(strtolower($name));
        $class = $controller . 'Controller';
        $file = in_array($controller, ['Wiki', 'Group', 'Push', 'Backup'], true)
            ? 'WikiGroupPushBackupController.php' : $class . '.php';
        $file = APP_DIR . '/controllers/' . $file;
        if (!is_file($file)) return null;
        require_once $file;
        $method = array_shift($parts) ?? 'index';
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $method) || !method_exists($class, $method)) return null;
        $action = new ReflectionMethod($class, $method);
        if (!$action->isPublic() || $action->isStatic() || $action->getDeclaringClass()->getName() === Controller::class
            || count($parts) < $action->getNumberOfRequiredParameters() || count($parts) > $action->getNumberOfParameters()) return null;
        foreach ($action->getParameters() as $i => $parameter) {
            if (isset($parts[$i]) && $parameter->getType()?->getName() === 'int'
                && (filter_var($parts[$i], FILTER_VALIDATE_INT) === false || (int) $parts[$i] < 0)) return null;
        }
        return [$class, $action->getName(), $parts];
    }

    private function parseUrl(): ?array
    {
        $route = '';
        if (isset($_GET['r'])) {
            if (!is_string($_GET['r'])) return null;
            $route = $_GET['r'];
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $route = ltrim($_SERVER['PATH_INFO'], '/');
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $script = dirname($_SERVER['SCRIPT_NAME']);
            if ($script !== '/' && strpos($uri, $script) === 0) {
                $uri = substr($uri, strlen($script));
            }
            $route = ltrim($uri, '/');
            // Skip index.php in path
            if (strpos($route, 'index.php') === 0) {
                $route = ltrim(substr($route, 9), '/');
            }
        }
        $route = trim($route, '/');
        if (empty($route)) return [];
        // Sanitize
        $parts = explode('/', $route);
        return array_values(array_filter($parts, fn($p) => $p !== ''));
    }

    private function notFound(string $msg = 'Page not found'): void
    {
        http_response_code(404);
        if (file_exists(APP_DIR . '/views/errors/404.php')) {
            view('errors/404', ['message' => $msg]);
        } else {
            echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($msg) . '</p>';
        }
    }
}
