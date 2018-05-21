<?php

namespace RobinMarechal\RestApi\Controllers;

use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Flysystem\Exception;
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

    private $controller;


    function dispatch($resource, $id = null, $relation = null, $relationId = null): JsonResponse
    {
        $restResponse = null;

        $this->controller = $this->prepareController($resource);

        switch ($this->request->getMethod()) {
            case 'GET':
                $restResponse = $this->handleGet($id, $relation, $relationId);
                break;
            case 'POST':
                // attach
                $restResponse = $this->handlePost($id, $relation, $relationId);
                break;
            case 'PUT':
                // sync
                $restResponse = $this->handlePut($id, $relation, $relationId);
                break;
            case 'DELETE':
                // detach
                $restResponse = $this->handleDelete($id, $relation, $relationId);
                break;
            default:
                return new JsonResponse(['data' => null]);
        }

        return $restResponse->toJsonResponse();
    }


    public function handleGet($id = null, $relation = null, $relationId = null): RestResponse
    {
        if ($relation) { // -> /api/users/5/posts
            $prefix = config('rest.controller_relation_function_prefix');
            $function = "{$prefix}{$relation}"; // -> get_posts

            return $this->controller->$function($id, $relationId);
        }
        else if ($id) { // -> /api/users/5
            return $this->controller->getById($id);
        }
        else { // -> /api/users
            return $this->controller->all();
        }
    }


    public function handlePost($id, $relation, $relationId): RestResponse
    {
        if ($id && $relation && $relationId) {
            return $this->controller->attach($id, $relation, $relationId);
        }

        return $this->controller->post();
    }


    public function handlePut($id, $relation, $relationId): RestResponse
    {
        if ($relation && $relationId) {
            return $this->controller->sync($id, $relation, $relationId);
        }

        return $this->controller->put($id);
    }


    public function handleDelete($id, $relation, $relationId): RestResponse
    {
        if ($relation && $relationId) {
            return $this->controller->detach($id, $relation, $relationId);
        }

        return $this->controller->delete($id);
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