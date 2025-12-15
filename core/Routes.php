<?php

namespace Core;

use App\Program;
use Core\controller\BaseController;
use Core\services\AuthenticationService;
use Core\Attributes\ApiController;
use Core\Attributes\HttpGet;
use Core\Attributes\HttpPost;
use Core\Attributes\HttpPut;
use Core\Attributes\HttpDelete;
use Core\Attributes\HttpPatch;

use Core\responses\Ok;
use Core\responses\NotFound;
use Core\responses\NoContent;

use Core\attributes\FromBody;
use Core\attributes\FromForm;
use Core\attributes\FromQuery;

use Core\responses\Unauthorized;

class Routes {
    private static array $httpMethodMap = [
        'GET' => \Core\Attributes\HttpGet::class,
        'POST' => \Core\Attributes\HttpPost::class,
        'PUT' => \Core\Attributes\HttpPut::class,
        'DELETE' => \Core\Attributes\HttpDelete::class,
        'PATCH' => \Core\Attributes\HttpPatch::class,
    ];

    static function init($path, $method){
        //Program::Main();

        $matchedController = self::matchPathAndController($path);        

        if($matchedController != null && $matchedController['class'] != null && $matchedController['path'] != null){
            $methodMatch = self::matchPathAndFunction($path, $method, $matchedController);
            BaseController::request($methodMatch);
        }else{
            http_response_code(404);
            echo 'No result';
        }
    }

    static function getControllerClasses() : array{
        $controllers = [];

        $root = dirname(__DIR__);

            // Recursive iterator for all .php files under $root
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $root,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Remember what classes exist before loading this file
                $before = get_declared_classes();

                // Load the file once
                require_once $file->getPathname();

                // New classes that appeared
                $after = get_declared_classes();
                $new   = array_diff($after, $before);

                foreach ($new as $class) {
                    if (is_subclass_of($class, BaseController::class)) {
                        $controllers[] = $class;
                    }
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

            $classAttributes = $reflectionClass->getAttributes(\Core\attributes\ApiController::class);
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
            $attributes = $method->getAttributes();

            
            $methodWithHTTPAttribute = $method->getAttributes(self::$httpMethodMap[strtoupper($requestMethod)]);

            $authRequired = false;

            foreach($attributes as $attribute){
                    if($attribute->getName() === 'Core\attributes\Authorize'){
                        $authRequired = true;
                    }
                }

            if(!empty($methodWithHTTPAttribute)){
                $attributeInstance = $methodWithHTTPAttribute[0]->newInstance();
                $methodPaths[] = [
                    'method' => $method,
                    'path' => $attributeInstance->path,
                    'auth' => $authRequired
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
                if($methodPath['auth'] === true){
                    if(!AuthenticationService::isAuthenticated()){
                        return [
                            'control' => new Unauthorized()
                        ];
                    }
                }

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