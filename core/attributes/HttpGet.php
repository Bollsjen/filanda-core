<?php

namespace Core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpGet{
    public function __construct(
        public string $path = ''
    ){}
}