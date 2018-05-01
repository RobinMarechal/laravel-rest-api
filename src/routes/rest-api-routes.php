<?php

$routePrefix = config("rest.route_prefix");

//Route::get("$routePrefix/test", "\RobinMarechal\RestApi\Controllers\RestControllerCo@test");

Route::prefix($routePrefix)->group(function () {
    Route::any('{resource}/{id?}/{relation?}/{relationId?}', "RobinMarechal\RestApi\Controllers\RestController@dispatch");
});