<?php

namespace Ordent\RamenRest\Providers;

use Illuminate\Support\ServiceProvider;

class RamenRestProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');
        $responseFactory = $this->app[\Ordent\RamenRest\Response\RestResponse::class];
        foreach (get_class_methods($responseFactory) as $method){
            \Response::macro($method, [$responseFactory, $method]);
        }
        \App::bind(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Ordent\RamenRest\Exception\Handler::class
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
