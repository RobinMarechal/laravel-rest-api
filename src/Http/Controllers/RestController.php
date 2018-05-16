<?php

namespace RobinMarechal\RestApi\Controllers;

use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RobinMarechal\RestApi\Rest\HandleRestRequest;
use RobinMarechal\RestApi\Rest\RestResponse;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use function camel_case;
use function class_exists;
use function strtoupper;
use function substr;

class RestController extends Controller
{
    public $request;


    function dispatch($resource, $id = null, $relation = null, $relationId = null): JsonResponse
    {
        $restResponse = null;

        switch ($this->request->getMethod()) {
            case 'GET':
                $restResponse = $this->handleGet($resource, $id, $relation, $relationId);
                break;
            case 'POST':
                $restResponse = $this->handlePost($resource);
                break;
            case 'PUT':
                $restResponse = $this->handlePut($resource, $id);
                break;
            case 'DELETE':
                $restResponse = $this->handleDelete($resource, $id);
                break;
            default:
                return new JsonResponse(['data' => null]);
        }

        return $restResponse->toJsonResponse();
    }


    public function handleGet($resource, $id = null, $relation = null, $relationId = null): RestResponse
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


    public function handlePost($resource): RestResponse
    {
        $controller = $this->prepareController($resource);

        return $controller->post();
    }


    public function handlePut($resource, $id): RestResponse
    {
        $controller = $this->prepareController($resource);

        return $controller->patch($id);
    }


    public function handleDelete($resource, $id): RestResponse
    {
        $controller = $this->prepareController($resource);

        return $controller->delete($id);
    }


    protected function prepareController($resource): Controller
    {
        $cfg = config('rest');

        $controllerPrefix = strtoupper($resource[0]) . camel_case(substr($resource, 1));
        $controllerPrefix = $cfg['controller_plural'] ? str_plural(str_singular($controllerPrefix)) : str_singular($controllerPrefix);

        $className = $controllerPrefix . "Controller";
        $classPath = $cfg['controller_namespace'] . $className;

        if (!class_exists($classPath)) {
            throw new ClassNotFoundException("Controller '$classPath' doesn't exist.", new ErrorException());
        }

        $instance = new $classPath($this->request);
        $instance->setTraitRequest($this->request);

        return $instance;
    }
}