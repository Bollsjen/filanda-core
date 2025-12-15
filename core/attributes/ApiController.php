<?php

namespace Core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ApiController {
    public function __construct (
        public string $path
    ){}
}