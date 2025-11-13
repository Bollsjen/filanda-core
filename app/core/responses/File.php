<?php
// App/core/responses/File.php

namespace App\core\responses;

use App\core\responses\ActionResult;

class File extends ActionResult {
    private $contentType;
    private $cacheMaxAge;

    public function __construct(mixed $data, string $contentType = 'application/octet-stream', int $cacheMaxAge = 86400){
        parent::__construct($data);
        $this->statusCode = 200;
        $this->contentType = $contentType;
        $this->cacheMaxAge = $cacheMaxAge;
    }

    public function send(): void {
        http_response_code($this->statusCode);
        header('Content-Type: ' . $this->contentType);
        header('Content-Length: ' . strlen($this->data));
        header('Cache-Control: public, max-age=' . $this->cacheMaxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cacheMaxAge) . ' GMT');
        
        echo $this->data; // Output raw binary, no JSON encoding
    }
}