<?php

namespace App\controller;

use App\core\controller\BaseController;
use App\core\attributes\ApiController;
use App\core\attributes\HttpGet;
use App\core\attributes\HttpPost;

use App\core\attributes\FromBody;
use App\core\attributes\FromForm;
use App\core\attributes\FromQuery;

use App\core\responses\Ok;
use App\core\responses\NotFound;

#[ApiController('/api/user')]
class UserController extends BaseController {

    #[HttpGet('')]
    public function get(){
        //return new NotFound();
        return new Ok('Cool');
    }

    #[HttpGet('/{id}')]
    public function getByID($id){
        return ['user' => $id];
    }

    #[HttpPost('')]
    public function postNewUser(#[FromBody] $userBody){
        return ['new_user' => $userBody];
    }
}