<?php
namespace Core\cors;

final class CorsOptions
{
    /** @var array<int,string> */
    public array $allowedOrigins = ['*'];
    /** @var array<int,string> */
    public array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
    /** @var array<int,string> */
    public array $allowedHeaders = ['Content-Type', 'Authorization'];
    public bool $allowCredentials = false;
    public ?int $maxAge = 600;

    /** @param string|array<int,string> $origins */
    public function allowOrigin(string|array $origins): self
    {
        $this->allowedOrigins = is_array($origins) ? $origins : [$origins];
        return $this;
    }

    /** @param array<int,string> $methods */
    public function allowMethods(array $methods): self
    {
        $this->allowedMethods = $methods;
        return $this;
    }

    /** @param array<int,string> $headers */
    public function allowHeaders(array $headers): self
    {
        $this->allowedHeaders = $headers;
        return $this;
    }

    public function withCredentials(bool $allow = true): self
    {
        $this->allowCredentials = $allow;
        return $this;
    }

    public function withMaxAge(?int $seconds): self
    {
        $this->maxAge = $seconds;
        return $this;
    }
}
