<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/


Route::group(['middleware' => config('laraupdater.middleware') ], function(){
    Route::get('updater.check', 'rohsyl\laraupdater\LaraUpdaterController@check')->name('laraupdater.check');
    Route::get('updater.currentVersion', 'rohsyl\laraupdater\LaraUpdaterController@getCurrentVersion')->name('laraupdater.current');
    Route::get('updater.update', 'rohsyl\laraupdater\LaraUpdaterController@update')->name('laraupdater.update');
});
