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

    private $DETACH = 'detach';

    private $ATTACH = 'attach';

    private $SYNC = 'sync';

    private $SYNC_WITHOUT_DETACHING = 'syncWithoutDetaching';


    public function getTraitRequest(): Request
    {
        return $this->traitRequest;
    }


    public function setTraitRequest(Request $request)
    {
        $this->traitRequest = $request;
        $this->postValues = $request->json()->all();
    }


    protected function userWantsAll(): bool
    {
        $allKeyword = config('rest.request_keywords.get_all');

        return $this->traitRequest->filled($allKeyword) && $this->traitRequest->get($allKeyword) == true;
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


    public function defaultGetById($class, $id, $ignoreParams = false): RestResponse
    {
        $data = QueryBuilder::prepareQueryOnlyRelationAndFieldsSelection($class)
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
        $array = QueryBuilder::prepareQuery($class)
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
        $data = $this->defaultGetById($class, $id, true)
                     ->getData();
        if ($data == null) {
            return RestResponse::make(null, Response::HTTP_BAD_REQUEST);
        }
        $data->update($this->traitRequest->json()->all());
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }
        else {
            $data = $this->defaultGetById($class, $id)
                         ->getData();
        }

        return RestResponse::make($data, Response::HTTP_OK);
    }


    public function sync($id, $relation, $relationId): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultSync($class, $id, $relation, $relationId);
    }


    public function defaultSync($class, $id, $relation, $relationId): RestResponse
    {
        if ($this->traitRequest->query(config('rest.request_keywords.sync_without_detaching'), true)) {
            return $this->syncAttachOrDetach($this->SYNC_WITHOUT_DETACHING, $class, $id, $relation, $relationId);
        }
        else {
            return $this->syncAttachOrDetach($this->SYNC, $class, $id, $relation, $relationId);
        }
    }


    public function all(): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultAll($class);
    }


    public function defaultAll($class): RestResponse
    {
        $data = QueryBuilder::prepareQuery($class)
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


    public function detach($id, $relation, $relationId): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultDetach($class, $id, $relation, $relationId);
    }


    public function defaultDetach($class, $id, $relation, $relationId): RestResponse
    {
        return $this->syncAttachOrDetach($this->DETACH, $class, $id, $relation, $relationId);
    }


    public function post(): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultPost($class);
    }


    public function defaultPost($class): RestResponse
    {
        $data = $class::create($this->postValues);
        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }
        else {
            $data = $this->defaultGetById($class, $data->id)
                         ->getData();
        }

        return RestResponse::make($data, Response::HTTP_CREATED);
    }


    public function attach($id, $relation, $relationId): RestResponse
    {
        $class = Helper::getRelatedModelClassName($this);

        return $this->defaultAttach($class, $id, $relation, $relationId);
    }


    public function defaultAttach($class, $id, $relation, $relationId): RestResponse
    {
        return $this->syncAttachOrDetach($this->ATTACH, $class, $id, $relation, $relationId);
    }


    /**
     * @param $attachMethod string sync, syncWithoutDetaching, attach or detach
     * @param $relation
     * @param $class
     * @param $id
     * @param $relationId
     * @return RestResponse
     */
    private function syncAttachOrDetach($attachMethod, $class, $id, $relation, $relationId)
    {
        $data = $this->defaultGetById($class, $id)
                     ->getData();

        if ($data == null) {
            return RestResponse::make(null, Response::HTTP_BAD_REQUEST);
        }

        if ($attachMethod === $this->DETACH) {
            $data->{$relation}()->{$attachMethod}([$relationId]);
        }
        else {
            $data->{$relation}()->{$attachMethod}([$relationId => $this->traitRequest->json()->all()]);
        }

        if ($this->userWantsAll()) {
            $data = $this->all()->getData();
        }
        else {
            // reload the data
            $data = $this->defaultGetById($class, $id)
                         ->getData();
        }

        return RestResponse::make($data, Response::HTTP_OK);
    }


    public function __call($method, $parameters): RestResponse
    {
        $prefix = config('rest.controller_relation_function_prefix');

        // 'strlen($method) > strlen($prefix)' is equivalent to 'isset($method[strlen($prefix)])'
        $prefixLength = strlen($prefix);
        if (strpos($method, $prefix) === 0 && isset($method[$prefixLength]) && is_array($parameters) && isset($parameters[0])) {
            $modelNamespace = config('rest.model_namespace');

            // Find the relation name (with first letter uppercase)
            $relation = substr($method, $prefixLength);

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
            return $this->defaultGetRelationResult($thisModelClassName, $id, $relatedModelClassName, $relation, $relatedId);

            return response()->json($resp->getData(), $resp->getCode());
        }
        FUNCTION_NOT_FOUND:
        throw new UndefinedFunctionException("Could not find method", new \ErrorException());
    }


    public function defaultGetRelationResult($class, $id, $relationClass, $relationName, $relationId = null): RestResponse
    {
        // relation function
        $withFunction = function ($query) use ($relationClass, $relationId) {
            $query = QueryBuilder::buildQuery($query, $relationClass);

            if (!is_null($relationId)) {
                $query->find($relationId);
            }
        };

        // Build the query with the relation
        $data = $class::with([$relationName => $withFunction])
                      ->find($id);

        // Nothing, we return null
        if (!isset($data)) {
            return RestResponse::make(null, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the wanted relation only
        $data = $data->$relationName;
        if (!is_null($relationId)) {
            $data = isset($data[0]) ? $data[0] : null;
        }

        return RestResponse::make($data, Response::HTTP_OK);
    }
}