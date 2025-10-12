<?php

namespace App\controller;

use App\core\controller\BaseController;
use App\core\attributes\ApiController;
use App\core\attributes\HttpGet;

#[ApiController('/api/user')]
class UserController extends BaseController {

    #[HttpGet('')]
    public function get(){
        return ['users' => []];
    }

    #[HttpGet('/{id}')]
    public function getByID($id){
        return ['user' => $id];
    }

}