<?php

namespace App\core;

use App\Program;
use App\core\controller\BaseController;
use App\core\Attributes\HttpGet;
use App\core\Attributes\HttpPost;
use App\core\Attributes\HttpPut;
use App\core\Attributes\HttpDelete;
use App\core\Attributes\HttpPatch;

class Routes {
    private static array $httpMethodMap = [
        'GET' => \App\core\Attributes\HttpGet::class,
        'POST' => \App\core\Attributes\HttpPost::class,
        'PUT' => \App\core\Attributes\HttpPut::class,
        'DELETE' => \App\core\Attributes\HttpDelete::class,
        'PATCH' => \App\core\Attributes\HttpPatch::class,
    ];

    static function init($path, $method){
        Program::Main();

        $pathArray = explode('/',$path);
        $pathArray = array_splice($pathArray, 2, count($pathArray));

        $matchedController = self::matchPathAndController($path);        

        if($matchedController != null && $matchedController['class'] != null && $matchedController['path'] != null){
            $methodMatch = self::matchPathAndFunction($path, $method, $matchedController);
            BaseController::request($methodMatch);
        }else{
            echo 'No result';
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

    static function matchPathAndController($path){
        $controllerClasses = self::getControllerClasses();

        $controllerPaths = [];

        foreach($controllerClasses as $class){
            $reflectionClass = new \ReflectionClass($class);

            $classAttributes = $reflectionClass->getAttributes(\App\core\attributes\ApiController::class);

            if (!empty($classAttributes)) {
                $attributeInstance = $classAttributes[0]->newInstance();
                $controllerPaths[] = [
                    'class' => $class,
                    'path' => $attributeInstance->path
                ];
            }
        }

        usort($controllerPaths, function($a, $b) {
            return strlen($b['path']) - strlen($a['path']);
        });

        foreach($controllerPaths as $controller) {
            if (str_starts_with($path, $controller['path'])) {
                return $controller;
            }
        }

        return null;
    }

    static function matchPathAndFunction($path, $requestMethod, $matchedController){
        $reflectionClass = new \ReflectionClass($matchedController['class']);

        $methods = $reflectionClass->getMethods();

        $methodPaths = [];

        foreach($methods as $method){
            
            $methodWithHTTPAttribute = $method->getAttributes(self::$httpMethodMap[strtoupper($requestMethod)]);

            if(!empty($methodWithHTTPAttribute)){
                $attributeInstance = $methodWithHTTPAttribute[0]->newInstance();
                $methodPaths[] = [
                    'method' => $method,
                    'path' => $attributeInstance->path
                ];
            }
        }

        usort($methodPaths, function($a, $b) {
            return strlen($b['path']) - strlen($a['path']);
        });

        foreach($methodPaths as $methodPath){

            $fullPattern = $matchedController['path'] . $methodPath['path'];

            $params = self::matchRoute($fullPattern, $path);

            if($params !== false){
                return [
                    'method' => $methodPath['method'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    static function matchRoute($pattern, $requestPath){
        $patternSegments = explode('/', trim($pattern, '/'));
        $requestSegments = explode('/', trim($requestPath, '/'));

        if(count($patternSegments) !== count($requestSegments)){
            return false;
        }

        $params = [];

        for($i = 0; $i < count($patternSegments); $i++){
            $patternSeg = $patternSegments[$i];
            $requestSeg = $requestSegments[$i];

            if(str_starts_with($patternSeg, '{') && str_ends_with($patternSeg, '}')){
                $paramName = substr($patternSeg, 1, -1);
                $params[$paramName] = $requestSeg;
            }else{
                if(strtolower($patternSeg) !== strtolower($requestSeg)){
                    return false;
                }
            }
        }

        return $params;
    }
}