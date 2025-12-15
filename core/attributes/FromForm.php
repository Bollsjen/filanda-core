<?php

namespace Core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromForm{
    public function __construct(public ?string $name = null) {}
}