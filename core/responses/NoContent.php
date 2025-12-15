<?php

namespace Core\responses;

use Core\responses\ActionResult;

class NoContent extends ActionResult {
    public function __construct() {
        parent::__construct(null);
        $this->statusCode = 204;
    }
    
    public function send(): void {
        http_response_code($this->statusCode);
        // No body for 204
    }
}