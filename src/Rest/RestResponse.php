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

        if (config('rest.allow_cors')) {
            $response->header('Access-Control-Allow-Origin', config('rest.allow_origins'));

            $methodsArray = config('rest.http_methods');
            $methodsString = join(', ', array_values($methodsArray));

            $response->header('Access-Control-Allow-Methods', $methodsString);
        }

        return $response;
    }
}