<?php
namespace Hmurich\Swagger\controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class SwaggerViewController extends Controller
{
    final public  function index(Request $request){
        return app('view')->make('hmurich-swagger::swagger');
    }

}
