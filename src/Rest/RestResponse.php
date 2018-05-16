<?php

namespace RobinMarechal\RestApi\Rest;

use Illuminate\Http\JsonResponse;

class RestResponse
{
    private $code;

    private $data;


    /**
     * ResponseData constructor.
     *
     * @param $code
     * @param $data
     */
    public function __construct($data, $code = 200)
    {
        $this->code = $code;
        $this->data = $data;
    }


    public static function make($data, $code = 200)
    {
        return new static($data, $code);
    }


    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }


    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }


    /**
     * Prepare Json response and its headers
     *
     * @param $data the data response. Will be wrapped into an array with key 'data'
     * @param $code the HTTP response code
     * @return Illuminate\Http\JsonResponse
     */
    public function toJsonResponse(): JsonResponse
    {
        $response = \response()->json(['data' => $this->data], $this->code);

        $response->header('Content-Type', 'application/json');

        if (config('rest.allow_cors')) {

            $methodsArray = config('rest.http_methods');
            $methodsString = join(', ', array_values($methodsArray));

            $response->header('Access-Control-Allow-Origin', config('rest.allow_origins'));
            $response->header('Access-Control-Allow-Methods', "$methodsString, OPTIONS");
            $response->header('Access-Control-Allow-Headers', "Content-Type, Origin");
            $response->header('Access-Control-Allow-Credentials', true);
        }

        return $response;
    }
}