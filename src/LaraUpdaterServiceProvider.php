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
        $this->publishes([  __DIR__ . '/../Config/laraupdater.php' => config_path('laraupdater.php'), ], 'laraupdater');
        $this->publishes([__DIR__ . '/../lang' => resource_path('lang')], 'laraupdater');
        $this->publishes([__DIR__.'/../views' => resource_path('views/vendor/laraupdater')], 'laraupdater');

        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/lang', 'laraupdater');

    }

    public function register()
    {
        //
    }
}
