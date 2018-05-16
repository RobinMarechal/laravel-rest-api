<?php
if(config('rest.allow_cors')){
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
}

$routePrefix = config("rest.route_prefix");

Route::prefix($routePrefix)->group(function () {
    Route::any('{resource}/{id?}/{relation?}/{relationId?}', "RobinMarechal\RestApi\Controllers\RestController@dispatch");
});