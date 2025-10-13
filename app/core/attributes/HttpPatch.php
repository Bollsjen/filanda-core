<?php

namespace App\core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpPatch{
    public function __construct(
        public string $path = ''
    ){}
}