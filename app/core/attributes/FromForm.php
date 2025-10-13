<?php

namespace App\core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromForm{
    public function __construct(public ?string $name = null) {}
}