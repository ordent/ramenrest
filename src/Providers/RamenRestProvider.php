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

        $this->publishes([
            __DIR__.'/../config/ramen.php' => config_path('ramen.php'),
        ]);

        
        
        // \Event::listen('Ordent\RamenRest\Events\FileHandlerEvent', 'Ordent\RamenRest\Listeners\FileHandlerListener@handle');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ramen.php', 'ramen'
        );
        $this->app->register(\Intervention\Image\ImageServiceProvider::class);
        $this->app->singleton('FileProcessor', function($app){
            return new \Ordent\RamenRest\Processor\FileProcessor;
        });
        $this->app->singleton('RestManager', function($app){
            return new \League\Fractal\Manager;
        });
    }
}
