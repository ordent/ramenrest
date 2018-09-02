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
        // adding default routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');
        // override response factory with package response class
        $responseFactory = $this->app[\Ordent\RamenRest\Response\RestResponse::class];
        foreach (get_class_methods($responseFactory) as $method){
            \Response::macro($method, [$responseFactory, $method]);
        }
        // override handler for resolving exception
        \App::bind(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Ordent\RamenRest\Exception\Handler::class
        );
        // publish config files
        $this->publishes([
            __DIR__.'/../config/ramen.php' => config_path('ramen.php')
        ]);
        // adding migration for file model type
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // merge config for default package config and setting up disks type filesystem
        $this->mergeConfigFrom(__DIR__.'/../config/ramen.php', 'ramen');
        $this->mergeConfigFrom(__DIR__.'/../config/filesystems-disks.php', 'filesystems.disks');
        // adding support to stores files in google cloud storage
        $this->app->register(\Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class);
        // adding support for image manipulation
        $this->app->register(\Intervention\Image\ImageServiceProvider::class);
        // adding support for files processor
        $this->app->singleton('FileProcessor', function($app){
            return new \Ordent\RamenRest\Processor\FileProcessor;
        });
        // adding support for rest manager
        $this->app->singleton('RestManager', function($app){
            return new \League\Fractal\Manager;
        });
    }
}
