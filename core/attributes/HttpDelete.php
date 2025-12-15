<?php

namespace Core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpDelete{
    public function __construct(
        public string $path = ''
    ){}
}