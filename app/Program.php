<?php

namespace App;

use Core\cors\Cors;
use Core\cors\CorsOptions;

class Program {
    private static ?Cors $cors = null;

    public static function Main(): void {
        $corseOptions = new CorsOptions();
        $corseOptions->allowOrigin(['http://localhost:8081'])
            ->allowMethods(['GET', 'POST', 'PUT', 'DELETE'])
            ->allowHeaders(['Content-Type', 'Authorization'])
            ->withCredentials(true);

        self::$cors = new Cors($corseOptions);
    }
}