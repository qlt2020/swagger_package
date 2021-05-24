<?php

namespace Hmurich\Swagger;

use GoldSpecDigital\ObjectOrientedOAS\Objects\{
    Info, MediaType, Operation, PathItem, Response, Schema, Tag, Parameter, RequestBody, SecurityRequirement, SecurityScheme, Components
};
use GoldSpecDigital\ObjectOrientedOAS\OpenApi;
use Storage;
use File;
use Illuminate\Support\Str;

class OpenApiGenerator
{
    private $info;
    private $tags = [];
    private $paths = [];
    private $api;

    function __construct (){
        $this->base();
        $this->info();
    }

    private  function base(){
        $this->api = OpenApi::create()
                            ->openapi(OpenApi::OPENAPI_3_0_2);

    }
    private function info(){
        $this->info = Info::create()
                            ->title(config('swagger.title'))
                            ->version(config('swagger.version'))
                            ->description(config('swagger.description'));

        $this->api = $this->api->info($this->info);
    }

    function do($routes){
        $this->parseRoutes($routes);
        $this->finish();

    }

    private  function finish(){
        if (count($this->tags))
            $this->api = call_user_func_array(array($this->api, "tags"), $this->tags);
        if (count($this->paths))
            $this->api = call_user_func_array(array($this->api, "paths"), $this->paths);

        $component = Components::create();
        $component = call_user_func_array(array($component, "schemas"), []);

        if (config('swagger.has_auth')){
            $sheme = SecurityScheme::oauth2('bearerAuth')->type('http')->scheme('bearer')->bearerFormat('JWT');
            $requrement = SecurityRequirement::create()->securityScheme($sheme);

            $this->api = $this->api->security($requrement);
            $component = $component->securitySchemes($sheme);
        }

        $this->api = $this->api->components($component);

        Storage::put(config('swagger.json_file'),  $this->api->toJson());
    }

    private function parseRoutes($routes){
        foreach ($routes as $route){
            $tag = $this->tag($route);

            $this->path($tag, $route);
        }
    }

    private function tag($route){
        $name = explode('\\', $route->controller);
        $name = array_slice($name, -2, 2, true);
        $name = implode('', $name);
        $name = str_replace('Controller', '', $name);


        $tag = Tag::create()->name($name);
        $tag = $tag->description(__('swagger.tag.'.Str::kebab($name)));

        $this->tags[$name] = $tag;

        return $tag;
    }

    private  function path($tag, $route){
        $name = __('swagger.path.'.Str::kebab($tag->name).'.'.$route->method);

        // create operation and calc operation type
        if ($route->type == 'POST')
            $operation = Operation::post($name);
        else if ($route->type == 'PUT')
            $operation = Operation::put($name);
        else if ($route->type == 'DELETE')
            $operation = Operation::delete($name);
        else
            $operation = Operation::get($name);

        $url_param = [];
        $url_param = Parameter::create()->name('Accept')->in('header')->required(true)->example('application/json');
        if (is_array($route->route_params)) {
            foreach ($route->route_params as $param) {
                $url_param[] = $this->routeParam($param, $tag, $route);
            }
        }

        if (in_array($route->type, ['POST', 'PUT']) && is_array($route->request_params)){
            $form_ar = [];
            foreach ($route->request_params as $param){
                $form_ar[] = $this->formParam($param, $tag, $route);
            }

            if (count($form_ar)){
                $sheme = Schema::object();
                $sheme =  call_user_func_array(array($sheme, "properties"), $form_ar);

                // calc required params
                $ar_req = $this->calcRequiredFormData($route->request_params);
                if (count($ar_req))
                   $sheme =  call_user_func_array(array($sheme, "required"), $ar_req);

                $form_data = RequestBody::create()->content(MediaType::create()->mediaType('multipart/form-data')->schema($sheme));

                $operation = $operation->requestBody($form_data);
            }
        }
        else if (is_array($route->request_params)){
            foreach ($route->request_params as $param){
                $url_param[] = $this->routeGetParam($param, $tag, $route);
            }
        }

        if (count($url_param))
            $operation = call_user_func_array(array($operation, "parameters"), $url_param);

        $operation = call_user_func_array(array($operation, "responses"), $this->responses());
        $operation = $operation->tags($tag);
        $operation = $operation->summary($name);
        $operation = $operation->noSecurity(false);

        // generate path
        $path = PathItem::create($name)
                        ->route($route->url.'?'.$route->type)
                        ->operations($operation);

        $path = $path->summary($name);

        // finish
        $this->paths[] = $path;

        return $path;
    }

    private  function routeParam($data, $tag, $route){
        $param = Parameter::path($data->name);
        $param = $param->description( __('swagger.param.'.Str::kebab($tag->name).'.'.$route->method.'.'.$data->name));
        $param = $param->name($data->name);

        $param = $param->required();

        if ($data->type == 'integer')
            $param = $param->schema(Schema::integer($data->name));
        else
            $param = $param->schema(Schema::string($data->name));

        return $param;
    }

    private function routeGetParam($data, $tag, $route){
        $param = Parameter::query($data->name);
        $param = $param->description( __('swagger.param.'.Str::kebab($tag->name).'.'.$route->method.'.'.$data->name));
        $param = $param->name($data->name);

        if ($data->required)
            $param = $param->required();

        if ($data->type == 'integer')
            $param = $param->schema(Schema::integer());
        else
            $param = $param->schema(Schema::string());

        return $param;
    }

    private  function calcRequiredFormData($items){
        $ar = [];
        foreach ($items as $i){
            if ($i->required)
                $ar[] = $i->name;
        }

        return $ar;
    }

    private  function formParam($data, $tag, $route){
        if ($data->type == 'integer')
            $param = Schema::integer($data->name);
        else if ($data->type == 'datetime')
            $param = Schema::string($data->name)->format('date-time');
        else if ($data->type == 'date')
            $param = Schema::string($data->name)->format('date');
        else if ($data->type == 'password')
            $param = Schema::string($data->name)->format('password');
        else if ($data->type == 'file')
            $param = Schema::string($data->name)->format(Schema::FORMAT_BINARY);
        else if ($data->type == 'boolean')
            $param = Schema::boolean($data->name);
        else
            $param = Schema::string($data->name);

        $param = $param->description( __('swagger.param.'.Str::kebab($tag->name).'.'.$route->method.'.'.$data->name));
        return $param;
    }

    private function responses(){
        $ar = [];
        $ar[] = Response::create()->statusCode(200)->description('OK');
        $ar[] = Response::notFound();
        $ar[] = Response::forbidden();
        $ar[] = Response::unauthorized();
        $ar[] = Response::badRequest();
        $ar[] = Response::internalServerError();

        return $ar;
    }

}
