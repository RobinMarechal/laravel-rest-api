<?php

$routePrefix = config("rest.route_prefix");

Route::prefix($routePrefix)->group(function () {
    Route::any('{resource}/{id?}/{relation?}/{relationId?}', "RobinMarechal\RestApi\Controllers\RestController@dispatch");
});