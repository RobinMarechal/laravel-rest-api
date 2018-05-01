<?php

namespace RobinMarechal\RestApi\Controllers;

use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RobinMarechal\RestApi\Rest\HandleRestRequest;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use function camel_case;
use function class_exists;
use function strtoupper;
use function substr;

class RestController extends Controller
{
    public $request;


    function __construct(Request $request)
    {
//        $request->header('Access-Control-Allow-Origin', '*')->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        $this->request = $request;
    }


    function dispatch($resource, $id = null, $relation = null, $relationId = null)
    {
        $response = null;

        switch ($this->request->getMethod()) {
            case 'GET':
                $response = $this->handleGet($resource, $id, $relation, $relationId);
            case 'POST':
                $response = $this->handlePost($resource);
            case 'PUT':
                $response = $this->handlePut($resource, $id);
            case 'DELETE':
                $response = $this->handleDelete($resource, $id);
        }

        if(config('rest.allow_cors')){
            $response->header('Access-Control-Allow-Origin', config('rest.allow_origins'));
            
            $methodsArray = config('rest.http_methods');
            $methodsString = join(', ', array_values($methodsArray));

            $response->header('Access-Control-Allow-Methods', $methodsString);
        }

        return $response;
    }


    public function handleGet($resource, $id = null, $relation = null, $relationId = null): Response
    {
        $controller = $this->prepareController($resource);

        if ($relation) { // -> /api/users/5/posts
            $function = camel_case("get_" . $relation);

            return $controller->$function($id, $relationId);
        }
        else if ($id) { // -> /api/users/5
            return $controller->getById($id);
        }
        else { // -> /api/users
            return $controller->all();
        }
    }


    public function handlePost($resource): Response
    {
        $controller = $this->prepareController($resource);

        return $controller->post();
    }


    public function handlePut($resource, $id): Response
    {
        $controller = $this->prepareController($resource);

        return $controller->patch($id);
    }


    public function handleDelete($resource, $id): Response
    {
        $controller = $this->prepareController($resource);

        return $controller->delete($id);
    }


    protected function prepareController($resource): HandleRestRequest
    {
        $cfg = config('rest');

        $controllerPrefix = strtoupper($resource[0]) . camel_case(substr($resource, 1));
        $controllerPrefix = $cfg['controller_plural'] ? str_plural($controllerPrefix) : str_singular($controllerPrefix);

        $className = $controllerPrefix . "Controller";
        $classPath = $cfg['controller_namespace'] . $className;

        if (!class_exists($classPath)) {
            throw new ClassNotFoundException("Controller '$classPath' doesn't exist.", new ErrorException());
        }

        $instance = new $classPath();
        $instance->setTraitRequest($this->request);

        return $instance;
    }
}