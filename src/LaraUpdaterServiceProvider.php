<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/
namespace pcinaglia\laraupdater;

use Illuminate\Support\ServiceProvider;

class LaraUpdaterServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Config/laraupdater.php' => config_path('laraupdater.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

    }

    public function register()
    {
        //
    }
}
