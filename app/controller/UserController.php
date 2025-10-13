<?php

namespace App\controller;

use App\core\controller\BaseController;
use App\core\attributes\ApiController;
use App\core\attributes\HttpGet;
use App\core\attributes\HttpPost;
use App\core\attributes\HttpDelete;

use App\core\attributes\FromBody;
use App\core\attributes\FromForm;
use App\core\attributes\FromQuery;

use App\core\attributes\Authorize;

use App\core\responses\Ok;
use App\core\responses\NotFound;
use App\core\responses\NoContent;

use App\managers\UserManager;

#[ApiController('/api/user')]
class UserController extends BaseController {

    private UserManager $manager;

    public function __construct(){
        $this->manager = new UserManager();
    }

    #[HttpGet('')]
    #[Authorize]
    public function get(){
        //return new NotFound();
        return new Ok('Cool');
    }

    #[HttpGet('/{id}')]
    public function getByID($id){
        return ['user' => $id];
    }

    #[HttpPost('/login/user')]
    public function postNewUser(#[FromBody] $userBody){
        $this->manager->login();
        return new Ok();
    }

    #[HttpDelete('/logout/user')]
    public function logoutUser(){
        $this->manager->logout();
        return new NoContent();
    }
}