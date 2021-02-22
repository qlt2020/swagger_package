<?php
namespace  Hmurich\Swagger;

use Symfony\Component\HttpFoundation\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use ReflectionClass;
use ReflectionParameter;
use StdClass;

class SwaggerRouteParser {
    public $type;
    public $url;

    public $route_params;
    public $request_params;

    public $route;
    public $controller;
    public $method;

    function parse(Route $route){
        $this->route = $route;

        $this->parseRouteData();
        $this->parseParams();

        $this->route = false;

        $obj = clone $this;
        return $obj;
    }

    private  function parseRouteData(){
        $ar = explode('@', $this->route->action['controller']);
        $this->controller = $ar[0];
        $this->method = $ar[1];

        $this->url = '/'.str_replace("?}", "}", $this->route->uri);

        $this->type = $this->route->methods[0];
    }

    private function parseParams(){
        $this->route_params = [];
        $this->request_params = [];

        $controller = new ReflectionClass($this->controller);
        $method = $controller->getMethod($this->method);

        foreach ($method->getParameters () as $param){
            $class = $param->getType();
            if ($class) {
                $class = $class->getName();
                $class = new ReflectionClass($class);
            }

            if ($class && $class->isSubclassOf(FormRequest::class)){
                $this->parseRequstParams($param, $class);
                continue;
            }

            if ($class && $class->isSubclassOf(Request::class))
                continue;


            if ($class && $class->isSubclassOf(Model::class)){
                $this->parseRouteModelParam($param, $class);
                continue;
            }

            $this->parseRouteOtherParam($param);
        }

    }

    private function parseRequstParams(ReflectionParameter $param, ReflectionClass $class){
        $res = new $class->name();
        $rules = $res->rules();

        foreach ($rules as $rule_name => $rule){
            $is_array = false;
            if (strpos($rule_name, '.*') !== false){
                $rule_name = str_replace(".*", "", $$rule_name);
                $is_array = true;
            }


            $obj = new StdClass();
            $obj->name = $rule_name;
            $obj->required = (strpos($rule, 'required') !== false ? true : false);

            if (strpos($rule, 'integer') !== false)
                $obj->type = 'integer';
            else if (strpos($rule, 'date') !== false)
                $obj->type = 'date';
            else if (strpos($rule, 'file') !== false)
                $obj->type = 'file';
            else if (strpos($rule, 'boolean') !== false)
                $obj->type = 'boolean';
            else if (strpos($rule, 'array') !== false && !$is_array)
                $obj->type = 'array';
            else
                $obj->type = 'string';

            if ($obj->type != 'array' && $is_array)
                $obj->type = 'array_'.$obj->type;

            $this->request_params[] =  $obj;

        }
    }

    private function parseRouteModelParam(ReflectionParameter $param, ReflectionClass $class){
        $obj = new StdClass();
        $obj->name = $param->getName();
        $obj->required = !$param->isOptional();
        $obj->type = 'integer';

        $this->route_params[] =  $obj;
    }

    private  function parseRouteOtherParam(ReflectionParameter $param){
        $obj = new StdClass();
        $obj->name = $param->getName();
        $obj->required = !$param->isOptional();
        $obj->type = 'string';

        $this->route_params[] =  $obj;
    }



}
