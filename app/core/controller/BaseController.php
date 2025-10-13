<?php

namespace App\core\controller;

use App\core\responses\NotFound;
use App\core\responses\Unauthorized;

class BaseController {
    public static function request($methodMatch){
        if($methodMatch === null){
            self::response(new NotFound());
            return;
        }

        if(isset($methodMatch['control'])){
            self::response($methodMatch['control']);
            return;
        }

        $reflectionClass = $methodMatch['method']->getDeclaringClass();
        $classInstance = $reflectionClass->newInstance();
        
        $parameters = $methodMatch['method']->getParameters();

        $arguments = [];

        foreach($parameters as $param){
            $paramName = $param->getName();

            $attributes = $param->getAttributes();

            $value = null;

            if(empty($attributes)){
                if(isset($methodMatch['params'][$paramName])){
                    $value = $methodMatch['params'][$paramName];
                }
            }else{
                $attribute = $attributes[0];

                switch($attribute->getName()){
                    case \App\core\attributes\FromBody::class:
                        $rawBody = file_get_contents('php://input');
                        $body = json_decode($rawBody, true);
                        $value = $body;
                        break;
                    
                    case \App\core\attributes\FromForm::class:
                        $value = $_POST ?? null;
                        break;

                    case \App\core\attributes\FromRoute::class:
                        $value = $methodMatch['params'][$paramName] ?? null;
                        break;

                    case \App\core\attributes\FromQuery::class:
                        $value = $_GET[$paramName] ?? null;
                        break;

                    default:
                        $value = $methodMatch['params'][$paramName] ?? null;
                        break;
                }
            }

            $arguments[] = $value;
        }
        
        $result = $methodMatch['method']->invoke($classInstance, $arguments);
        self::response($result);
    }

    public static function response($result){
        if($result instanceof \App\core\responses\ActionResult){
            $result->send();
        }
        else if(is_array($result) || is_object($result)){
            header('Content-Type: application/json');
            $json = json_encode($result);
            echo $json;
        }else{
            header('Content-Type: text/plain');
            echo $result;
        }
    }

}