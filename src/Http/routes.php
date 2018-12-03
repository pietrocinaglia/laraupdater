<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/

Route::get('updater.check', 'pcinaglia\laraupdater\LaraUpdaterController@check');
Route::get('updater.currentVersion', 'pcinaglia\laraupdater\LaraUpdaterController@getCurrentVersion');

Route::group(['middleware' => config('laraupdater.middleware') ], function(){
    Route::get('updater.update', 'pcinaglia\laraupdater\LaraUpdaterController@update');
});
