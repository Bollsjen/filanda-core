<?php

namespace Core\models;

class AuthUser {
    public int $id;
    public string $email;
    public array $roles = [];
}