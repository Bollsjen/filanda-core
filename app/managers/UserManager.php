<?php

namespace App\managers;

use App\core\services\AuthenticationService;
use App\core\models\AuthUser;

class UserManager {
    public function login(){
        $user = new AuthUser();

        $user->id = 1;
        $user->email = 'example@example.com';

        AuthenticationService::startSecureSession();
        AuthenticationService::login($user, true);
    }

        public function logout(){
        AuthenticationService::logout();
        return true;
    }
}