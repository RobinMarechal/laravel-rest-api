<?php

namespace RobinMarechal\RestApi\Controllers;

use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Http\Request;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use function camel_case;
use function class_exists;
use function strtoupper;
use function substr;

class RestController extends Controller
{
    public $request;
    public $controller;


    function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function handleGet($resource, $id = null, $relation = null, $relationId = null)
    {
        $this->prepareController($resource);

        if ($relation) { // -> /api/users/5/posts
            $function = camel_case("get_" . $relation);

            return $this->controller->$function($id, $relationId);
        }
        else if ($id) { // -> /api/users/5
            return $this->controller->getById($id);
        }
        else { // -> /api/users
            return $this->controller->all();
        }
    }


    public function handlePost($resource)
    {
        $this->prepareController($resource);

        return $this->controller->post();
    }


    public function handlePut($resource, $id)
    {
        $this->prepareController($resource);

        return $this->controller->patch($id);
    }


    public function handleDelete($resource, $id)
    {
        $this->prepareController($resource);

        return $this->controller->delete($id);
    }


    protected function prepareController($resource)
    {
        $cfg = config('rest');

        $controllerPrefix = strtoupper($resource[0]) . camel_case(substr($resource, 1));
        $controllerPrefix = $cfg['controller_plural'] ? str_plural($controllerPrefix) : str_singular($controllerPrefix);

        $className =  $controllerPrefix . "Controller";
        $classPath = $cfg['controller_namespace'] . $className;

        if (!class_exists($classPath)) {
            throw new ClassNotFoundException("Controller '$classPath' doesn't exist.", new ErrorException());
        }

        $instance = new $classPath();
        $instance->setTraitRequest($this->request);
        $this->controller = $instance;
    }
}