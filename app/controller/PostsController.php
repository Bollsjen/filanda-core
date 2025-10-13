<?php

namespace App\controller;

use App\core\controller\BaseController;
use App\core\attributes\ApiController;

#[ApiController('/api/posts')]
class PostsController extends BaseController {

    #[HttpGet('')]
    public function get(){
        return ['posts' => []];
    }

    #[HttpGet('/{id}')]
    public function getByID($id){
        return ['post' => $id];
    }

}