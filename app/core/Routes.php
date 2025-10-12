<?php

namespace App\core;

use App\Program;
use App\core\controller\BaseController;

class Routes {
    static function init($path, $method){
        $pathArray = explode('/',$path);
        $pathArray = array_splice($pathArray, 2, count($pathArray));

        $controllerClasses = self::getControllerClasses();

        //var_dump($controllerClasses);

        foreach($controllerClasses as $class){
            $reflectionClass = new \ReflectionClass($class);

            $classAttributes = $reflectionClass->getAttributes();

            foreach($classAttributes as $attribute){
                $attributeInstance = $attribute->newInstance();
                echo $attributeInstance->path;
            }
        }
    }

    static function getControllerClasses() : array{
        $controllers = [];

        $files = glob(__DIR__ . '/../controller/*.php');

        foreach($files as $file){
            $classesBefore = get_declared_classes();

            require_once $file;

            $classesAfter = get_declared_classes();

            $newClasses = array_diff($classesAfter, $classesBefore);

            foreach($newClasses as $class){
                if(is_subclass_of($class, BaseController::class)){
                    $controllers[] = $class;
                }
            }
        }

        return $controllers;
    }

    static function matchPathAndController($path, $controllerPath){
        
    }
}