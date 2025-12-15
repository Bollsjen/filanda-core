<?php

namespace App\controller;

use Core\controller\BaseController;
use Core\attributes\ApiController;
use Core\attributes\HttpGet;

use Core\responses\Ok;

#[ApiController('/api/posts')]
class PostsController extends BaseController {
#[HttpGet('')]
public function get(){
    return new Ok([
        'posts' => [
            [
                'userID' => 1,
                'content' => 'Hello World!'
            ],
            [
                'userID' => 2,
                'content' => 'Second Post'
            ]
        ]
    ]);
}


    #[HttpGet('/{id}')]
    public function getByID($id){
        return ['post' => $id];
    }

}