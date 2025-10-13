<?php

namespace App\core\cors;

use App\core\cors\CorsOptions;

class Cors {
    private CorsOptions $options;
    
    public function __construct(CorsOptions $options) {
        $this->options = $options;
        $this->handle();
    }
    
    public function handle(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Check if origin is allowed
        if (!in_array('*', $this->options->allowedOrigins) && 
            !in_array($origin, $this->options->allowedOrigins)) {
            $origin = $this->options->allowedOrigins[0] ?? '*';
        }
        
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: " . implode(', ', $this->options->allowedMethods));
        header("Access-Control-Allow-Headers: " . implode(', ', $this->options->allowedHeaders));
        
        if ($this->options->allowCredentials) {
            header("Access-Control-Allow-Credentials: true");
        }
        
        if ($this->options->maxAge) {
            header("Access-Control-Max-Age: {$this->options->maxAge}");
        }
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}