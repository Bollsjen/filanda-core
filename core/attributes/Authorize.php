<?php

namespace Core\attributes;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Authorize {
    public function __construct(
            public ?array $roles = null
        ) {}
}