<?php
/**
 * Created by PhpStorm.
 * User: robin
 * Date: 24/02/18
 * Time: 15:47
 */

namespace RobinMarechal\RestApi\Rest;

use Carbon\Carbon;
use RobinMarechal\RestApi\Http\Helper;
use Symfony\Component\Debug\Exception\UndefinedFunctionException;
use Symfony\Component\HttpFoundation\Response;
use function str_singular;

trait HandleRestRequest
{
    protected $traitRequest;
    protected $postValues;


    public function getTraitRequest()
    {
        return $this->traitRequest;
    }


    public function setTraitRequest($request)
    {
        $this->traitRequest = $request;
        $this->postValues = $request->json()->all();
    }


    /*
     * ------------------------------------------------------------------
     * ------------------------------------------------------------------
     */

    public function getById($id)
    {
        $class = Helper::getRelatedModelClassName($this);
        $resp = $this->defaultGetById($class, $id);

        return \response()->json($resp->getData(), $resp->getCode());
    }


    public function defaultGetById($class, $id)
    {
        $data = QueryBuilder::getPreparedQuery($class)
                            ->find($id);

        return new ResponseData($data, Response::HTTP_OK);
    }


    public function getFromTo($from, $to)
    {
        $class = Helper::getRelatedModelClassName($this);
        $resp = $this->defaultGetFromTo($class, $from, $to);

        return \response()->json($resp->getData(), $resp->getCode());
    }


    public function defaultGetFromTo($class, $from, $to, $field = null)
    {
        if(!$field){
            $field = config('rest.default_temporal_field');
        }

        $fromCarbon = Carbon::parse($from);
        $toCarbon = Carbon::parse($to);
        $array = QueryBuilder::getPreparedQuery($class)
                             ->whereBetween($field, [$fromCarbon, $toCarbon])
                             ->get();

        return new ResponseData($array, Response::HTTP_OK);
    }


    public function put($id)
    {
        $class = Helper::getRelatedModelClassName($this);
        $resp = $this->defaultPut($class, $id);

        return \response()->json($resp->getData(), $resp->getCode());
    }


    public function defaultPut($class, $id)
    {
        $data = $this->defaultGetById($class, $id)
                     ->getData();
        if ($data == null) {
            return new ResponseData(null, Response::HTTP_BAD_REQUEST);
        }
        $data->update($this->traitRequest->all());
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }

        return new ResponseData($data, Response::HTTP_OK);
    }


    protected function userWantsAll()
    {
        $allKeyword = config('rest.request_keywords.get_all');
        return $this->traitRequest->filled($allKeyword) && $this->traitRequest->get($allKeyword) == true;
    }


    public function all()
    {
        $class = Helper::getRelatedModelClassName($this);
        $resp = $this->defaultAll($class);

        return \response()->json($resp->getData(), $resp->getCode());
    }


    public function defaultAll($class)
    {
        $data = QueryBuilder::getPreparedQuery($class)
                            ->get();

        return new ResponseData($data, Response::HTTP_OK);
    }


    public function delete($id)
    {
        $class = Helper::getRelatedModelClassName($this);
        $resp = $this->defaultDelete($class, $id);

        return \response()->json($resp->getData(), $resp->getCode());
    }


    public function defaultDelete($class, $id)
    {
        $data = $class::find($id);
        if ($data == null) {
            return new ResponseData(null, Response::HTTP_BAD_REQUEST);
        }
        $data->delete();
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }

        return new ResponseData($data, Response::HTTP_OK);
    }


    public function post()
    {
        $class = Helper::getRelatedModelClassName($this);
        $resp = $this->defaultPost($class);

        return \response()->json($resp->getData(), $resp->getCode());
    }


    public function defaultPost($class)
    {
        $data = $class::create($this->postValues);
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }

        return new ResponseData($data, Response::HTTP_CREATED);
    }


    public function __call($method, $parameters)
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
            $resp = $this->defaultGetRelationResultOfId($thisModelClassName, $id, $relatedModelClassName, $relation, $relatedId);

            return response()->json($resp->getData(), $resp->getCode());
        }
        FUNCTION_NOT_FOUND:
        throw new UndefinedFunctionException();
    }


    public function defaultGetRelationResultOfId($class, $id, $relationClass, $relationName, $relationId = null)
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
            return new ResponseData(null, Response::HTTP_NOT_FOUND);
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

        return new ResponseData($data, Response::HTTP_OK);
    }


    /**
     * @param $class        string the model (usually associated with the current controller) class name
     * @param $id           int the id of the resource
     * @param $relationName string the relation name. This can be chained relations, separated with '.' character.
     *
     * @warning if chained relations, all of these (but the last) have to be BelongsTo relations (singular relations),
     *          otherwise this will fail
     * @return ResponseData the couple (json, Http code)
     */
    public function defaultGetRelationResult($class, $id, $relationName)
    {
        // Find the data with it's relation
        $data = $class::with([$relationName => function ($query) use ($class) {
            QueryBuilder::buildQuery($query, $class);
        }])
                      ->find($id);
        // Nothing, we send null
        if (!isset($data)) {
            return new ResponseData(null, Response::HTTP_NOT_FOUND);
        }

        // Go forward in the relations
        $rels = explode('.', $relationName);
        foreach ($rels as $r) {
            $data = $data->$r;
        }

        return new ResponseData($data, Response::HTTP_OK);
    }
}