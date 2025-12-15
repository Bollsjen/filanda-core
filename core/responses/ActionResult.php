<?php

namespace Core\responses;

abstract class ActionResult {
    protected mixed $data;
    protected int $statusCode;

    public function __construct(mixed $data = null){
        $this->data = $data;
    }

    abstract public function send(): void;
}