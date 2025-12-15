<?php

namespace App\controller;

use Core\controller\BaseController;
use Core\attributes\ApiController;
use Core\attributes\HttpGet;
use Core\attributes\HttpPost;
use Core\attributes\HttpDelete;

use Core\attributes\FromBody;
use Core\attributes\FromForm;
use Core\attributes\FromQuery;

use Core\attributes\Authorize;

use Core\responses\Ok;
use Core\responses\NotFound;
use Core\responses\NoContent;

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