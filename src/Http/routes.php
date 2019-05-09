<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/


Route::group(['middleware' => config('laraupdater.middleware') ], function(){
    Route::get('updater.check', 'pcinaglia\laraupdater\LaraUpdaterController@check')->name('laraupdater.check');
    Route::get('updater.currentVersion', 'pcinaglia\laraupdater\LaraUpdaterController@getCurrentVersion')->name('laraupdater.current');
    Route::get('updater.update', 'pcinaglia\laraupdater\LaraUpdaterController@update')->name('laraupdater.update');
});
