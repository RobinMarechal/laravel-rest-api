<?php

namespace RobinMarechal\RestApi\Rest;

use Carbon\Carbon;
use Illuminate\Http\Request;
use RobinMarechal\RestApi\Http\Helper;
use Symfony\Component\Debug\Exception\UndefinedFunctionException;
use Symfony\Component\HttpFoundation\Response;
use function str_singular;

trait HandleRestRequest
{
    protected $traitRequest;

    protected $postValues;


    public function getTraitRequest(): Request
    {
        return $this->traitRequest;
    }


    public function setTraitRequest(Request $request)
    {
        $this->traitRequest = $request;
        $this->postValues = $request->json()->all();
    }


    /*
     * ------------------------------------------------------------------
     * ------------------------------------------------------------------
     */

    public function getById($id): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultGetById($class, $id);
    }


    public function defaultGetById($class, $id): RestResponse
    {
        $data = QueryBuilder::getPreparedQuery($class)
                            ->find($id);

        return RestResponse::make($data, Response::HTTP_OK);
    }


    public function getFromTo($from, $to): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultGetFromTo($class, $from, $to);
    }


    public function defaultGetFromTo($class, $from, $to, $field = null): RestResponse
    {
        if (!$field) {
            $field = config('rest.default_temporal_field');
        }

        $fromCarbon = Carbon::parse($from);
        $toCarbon = Carbon::parse($to);
        $array = QueryBuilder::getPreparedQuery($class)
                             ->whereBetween($field, [$fromCarbon, $toCarbon])
                             ->get();

        return RestResponse::make($array, Response::HTTP_OK);
    }


    public function put($id): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultPut($class, $id);
    }


    public function defaultPut($class, $id): RestResponse
    {
        $data = $this->defaultGetById($class, $id)
                     ->getData();
        if ($data == null) {
            return RestResponse::make(null, Response::HTTP_BAD_REQUEST);
        }
        $data->update($this->traitRequest->all());
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }

        return RestResponse::make($data, Response::HTTP_OK);
    }


    protected function userWantsAll(): bool
    {
        $allKeyword = config('rest.request_keywords.get_all');

        return $this->traitRequest->filled($allKeyword) && $this->traitRequest->get($allKeyword) == true;
    }


    public function all(): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultAll($class);
    }


    public function defaultAll($class): RestResponse
    {
        $data = QueryBuilder::getPreparedQuery($class)
                            ->get();

        return RestResponse::make($data, Response::HTTP_OK);
    }


    public function delete($id): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultDelete($class, $id);
    }


    public function defaultDelete($class, $id): RestResponse
    {
        $data = $class::find($id);
        if ($data == null) {
            return RestResponse::make(null, Response::HTTP_BAD_REQUEST);
        }
        $data->delete();
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }

        return RestResponse::make($data, Response::HTTP_OK);
    }


    public function post(): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultPost($class);
    }


    public function defaultPost($class): RestResponse
    {
        dd('hello');
        dd('hello', $this->request->all());
        $data = $class::create($this->postValues);
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }

        return RestResponse::make($data, Response::HTTP_CREATED);
    }


    public function __call($method, $parameters): RestResponse
    {
        if (strpos($method, "get_") == 0 && strlen($method) > 3 && is_array($parameters) && isset($parameters[0])) {
            $modelNamespace = config('rest.model_namespace');

            // Find the relation name (with first letter uppercase)
            $relation = substr($method, 3);

            // Find relation's model class name
            $relatedModelClassName = str_singular($relation);
            $relatedModelClassName = $modelNamespace . strtoupper(substr($relatedModelClassName, 0, 1)) . substr($relatedModelClassName, 1);

            // Find the model class name
            $thisModelClassName = Helper::getRelatedModelClassName($this);

            // Find the related ID, if there is one
            $id = $parameters[0];
            $relatedId = null;
            if (isset($parameters[1])) {
                $relatedId = $parameters[1];
            }

            // Id id not numeric, fail
            if (!is_numeric($id) || ($relatedId && !is_numeric($relatedId))) {
                GOTO FUNCTION_NOT_FOUND;
            }

            // Execute the query
            return $this->defaultGetRelationResultOfId($thisModelClassName, $id, $relatedModelClassName, $relation, $relatedId);

            return response()->json($resp->getData(), $resp->getCode());
        }
        FUNCTION_NOT_FOUND:
        throw new UndefinedFunctionException();
    }


    public function defaultGetRelationResultOfId($class, $id, $relationClass, $relationName, $relationId = null): RestResponse
    {
        // No relation, redirect the request
        if ($relationId == null) {
            return $this->defaultGetRelationResult($class, $id, $relationName);
        }

        // Build the query with the relation
        $data = $class::with([
            $relationName => function ($query) use ($relationClass) {
                QueryBuilder::buildQuery($query, $relationClass);
            }])
                      ->where((new $class())->getTable() . '.id', $id)
                      ->first();

        // Nothing, we return null
        if (!isset($data)) {
            return RestResponse::make(null, Response::HTTP_NOT_FOUND);
        }

        // Find the wanted relation
        $rels = explode('.', $relationName);

        // Go forward in the relations
        foreach ($rels as $r) {
            $data = $data->$r;
        }

        // Apply a filter in the final collection
        $data = $data->where('id', "=", $relationId)
                     ->first();

        return RestResponse::make($data, Response::HTTP_OK);
    }


    /**
     * @param $class        string the model (usually associated with the current controller) class name
     * @param $id           int the id of the resource
     * @param $relationName string the relation name. This can be chained relations, separated with '.' character.
     *
     * @warning if chained relations, all of these (but the last) have to be BelongsTo relations (singular relations),
     *          otherwise this will fail
     * @return RestResponse the couple (json, Http code)
     */
    public function defaultGetRelationResult($class, $id, $relationName): RestResponse
    {
        // Find the data with it's relation
        $data = $class::with([$relationName => function ($query) use ($class) {
            QueryBuilder::buildQuery($query, $class);
        }])
                      ->find($id);
        // Nothing, we send null
        if (!isset($data)) {
            return RestResponse::make(null, Response::HTTP_NOT_FOUND);
        }

        // Go forward in the relations
        $rels = explode('.', $relationName);
        foreach ($rels as $r) {
            $data = $data->$r;
        }

        return RestResponse::make($data, Response::HTTP_OK);
    }
}