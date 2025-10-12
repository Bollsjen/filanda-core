<?php

// Autoloading (you'd use Composer's autoloader in real project)
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/app/core/Routes.php';

// Get the request path and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Basic routing decision
if (str_starts_with($requestPath, '/api')) {
    // Handle as API request (return JSON typically)
    handleApiRequest($requestPath, $requestMethod);
} else {
    // Handle as web request (return HTML views)
    handleWebRequest($requestPath, $requestMethod);
}

function handleApiRequest($path, $method) {
    header('Content-Type: application/json');
    // Your API routing/controller logic here
    \App\core\Routes::init($path, $method);
}

function handleWebRequest($path, $method) {
    // Your web routing/controller logic here
    echo '<h1>Web handler</h1><p>Path: ' . htmlspecialchars($path) . '</p>';
}