<?php

namespace Core\responses;

use Core\responses\ActionResult;

class Ok extends ActionResult {
    public function __construct(mixed $data = null){
        parent::__construct($data);
        $this->statusCode = 200;
    }

    public function send(): void {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->data);
    }
}