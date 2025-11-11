<?php

namespace App\Core;

/**
 * Base controller class for all controllers.
 * It provides common functionalities for handling requests and rendering views.
 */
class Controller
{
    /**
     * Render a view with the given data.
     * @param string $view The path to the view file.
     * @param array $data The data to pass to the view.
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data); // Extract the data array into variables
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }
}
