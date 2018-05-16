<?php
if(config('rest.allow_cors')){
    header('Access-Control-Allow-Origin: *', true);
    header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin", true);
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS", true);
}
$routePrefix = config("rest.route_prefix");

Route::prefix($routePrefix)->group(function () {
    Route::any('{resource}/{id?}/{relation?}/{relationId?}', "RobinMarechal\RestApi\Controllers\RestController@dispatch");
});