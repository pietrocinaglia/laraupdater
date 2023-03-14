<?php
/*
* @author: Pietro Cinaglia
* https://github.com/pietrocinaglia
*/

namespace pcinaglia\laraupdater;

use Illuminate\Support\ServiceProvider;
use pcinaglia\laraupdater\Commands\CommandCheck;
use pcinaglia\laraupdater\Commands\CommandCurrentVersion;
use pcinaglia\laraupdater\Commands\CommandUpdate;

class LaraUpdaterServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/laraupdater.php' => config_path('laraupdater.php'),], 'laraupdater');
        $this->publishes([__DIR__ . '/../lang' => lang_path()], 'laraupdater');
        $this->publishes([__DIR__ . '/../views' => resource_path('views/vendor/laraupdater')], 'laraupdater');

        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/lang', 'laraupdater');


        $this->commands(
            [
                CommandUpdate::class,
                CommandCheck::class,
                CommandCurrentVersion::class
            ]
        );
    }

    public function register()
    {
        //
    }
}
