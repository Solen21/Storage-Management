<?php

namespace App\Core;

class App
{
    protected $controller = 'AuthController'; // Default controller
    protected $method = 'login'; // Default method
    protected $params = [];

    public function __construct()
    {
        // Ensure the base Controller class is loaded before any specific controller
        // that extends it. This resolves the "Class 'App\Core\Controller' not found" error.
        require_once __DIR__ . '/Controller.php';

        $url = $this->parseUrl();

        // Controller resolution
        if (isset($url[0])) {
            $controllerName = ucfirst($url[0]) . 'Controller';
            $controllerFilePath = '../app/Controllers/' . $controllerName . '.php';

            if (file_exists($controllerFilePath)) {
                $this->controller = $controllerName;
                unset($url[0]);
            }
            // If the requested controller doesn't exist, it will fall back to the default.
        }

        // Load the determined controller file
        $controllerFilePath = '../app/Controllers/' . $this->controller . '.php';
        if (!file_exists($controllerFilePath)) {
            // This should ideally not happen if default controller is always present.
            // In a real application, you might throw an exception or redirect to a 404 page.
            die("Error: Controller file not found: " . $controllerFilePath);
        }
        require_once $controllerFilePath;

        // Instantiate the controller with its full namespace
        $controllerClassName = 'App\\Controllers\\' . $this->controller;
        $this->controller = new $controllerClassName();

        // Method resolution
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
            // If the requested method doesn't exist, it will fall back to the default.
        }

        // Parameters
        $this->params = $url ? array_values($url) : [];

        // Call the controller method with parameters
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl()
    {
        if (isset($_GET['url'])) {
            return $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return []; // Return an empty array if no URL is provided, preventing null access.
    }
}