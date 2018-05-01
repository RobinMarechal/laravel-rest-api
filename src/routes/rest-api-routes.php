<?php

$routePrefix = config("rest.route_prefix");

//Route::get("$routePrefix/test", "\RobinMarechal\RestApi\Controllers\RestControllerCo@test");

Route::prefix($routePrefix)->group(function () {
//    Route::get("{resource}/{id?}/{relation?}/{relationId?}", "RobinMarechal\RestApi\Controllers\RestController@handleGet")->name('api.handle_get');
//    Route::post("{resource}", "RobinMarechal\RestApi\Controllers\RestController@handlePost")->name('api.handle_post');
//    Route::put("{resource}/{id}", "RobinMarechal\RestApi\Controllers\RestController@handlePut")->name('api.handle_put');
//    Route::patch("{resource}/{id}", "RobinMarechal\RestApi\Controllers\RestController@handlePut")->name('api.handle_patch');
//    Route::delete("{resource}/{id}", "RobinMarechal\RestApi\Controllers\RestController@handleDelete")->name('api.handle_delete');
    Route::any('{resource}/{id?}/{relation?}/{relationId?}', "RobinMarechal\RestApi\Controllers\RestController@dispatch");
});