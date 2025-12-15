<?php

namespace Core\responses;

use Core\responses\ActionResult;

class Unauthorized extends ActionResult {
    public function __construct() {
        parent::__construct(null);
        $this->statusCode = 401;
    }
    
    public function send(): void {
        http_response_code($this->statusCode);
        // No body for 204
    }
}