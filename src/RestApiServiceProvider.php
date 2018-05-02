<?php

namespace RobinMarechal\RestApi;

use App\Http\Middleware\Cors;
use Illuminate\Support\ServiceProvider;
use RobinMarechal\RestApi\Commands\ApiTablesCommand;
use RobinMarechal\RestApi\Controllers\ApiController;
use RobinMarechal\RestApi\Controllers\RestController;

class RestApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Route::pattern('resource', '.+s'); // plural
        Route::pattern('id', '[0-9]+'); // number
        Route::pattern('relationId', '[0-9]+'); // number

        $this->mergeConfigFrom(__DIR__ . '/config/rest.php', 'rest');
        
        $this->publishes([
            __DIR__.'/config/rest.php' => config_path('rest.php'),
        ]);

        $this->app->make(RestController::class);

        $this->loadRoutesFrom(__DIR__ . '/routes/rest-api-routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ApiTablesCommand::class
            ]);
        }
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
