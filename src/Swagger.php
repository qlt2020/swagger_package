<?php

namespace Hmurich\Swagger;

use Illuminate\Support\Facades\Route;

class Swagger
{
    private $api_prefix = '';
    private $route_parser = '';
    private $routes = [];

    function __construct (){
        $this->setApiPrefix();
    }

    private function setApiPrefix(){
        $this->api_prefix = config('swagger.api_prefix');
        $this->route_parser = new SwaggerRouteParser();
        $this->generator = new OpenApiGenerator();
    }

    static function init(){
        $el = new Swagger();
        $el->do();
    }

    function do(){
        $this->parseRoutes();
        $this->generateOpenApi();
    }

    private function parseRoutes(){
        foreach (Route::getRoutes() as $route){
            if (stripos($route->uri, $this->api_prefix) === false) {
                continue;
            }
            $this->routes[] = $this->route_parser->parse($route);
        }
    }


    private function generateOpenApi(){
        $this->generator->do($this->routes);
    }
}
