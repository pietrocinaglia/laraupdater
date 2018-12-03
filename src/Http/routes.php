<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/

Route::get('updater.check', 'pietrocinaglia\laraupdater\LaraUpdaterController@check');
Route::get('updater.currentVersion', 'pietrocinaglia\laraupdater\LaraUpdaterController@getCurrentVersion');

Route::group(['middleware' => config('laraupdater.middleware') ], function(){
    Route::get('updater.update', 'pietrocinaglia\laraupdater\LaraUpdaterController@update');
});
