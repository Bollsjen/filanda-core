<?php

namespace App\core\cors;

class CorsOptions {
    public array $allowedOrigins = ['*'];
    public array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
    public array $allowedHeaders = ['*'];
    public bool $allowCredentials = false;
    public ?int $maxAge = 3600;
    
    public function allowOrigin(string|array $origins): self {
        $this->allowedOrigins = is_array($origins) ? $origins : [$origins];
        return $this;
    }
    
    public function allowMethods(array $methods): self {
        $this->allowedMethods = $methods;
        return $this;
    }
    
    public function allowHeaders(array $headers): self {
        $this->allowedHeaders = $headers;
        return $this;
    }
    
    public function withCredentials(bool $allow = true): self {
        $this->allowCredentials = $allow;
        return $this;
    }
}