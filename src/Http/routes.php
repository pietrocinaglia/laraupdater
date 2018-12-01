<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/

Route::get('updater.check', 'salihkiraz\laraupdater\LaraUpdaterController@check');
Route::get('updater.currentVersion', 'salihkiraz\laraupdater\LaraUpdaterController@getCurrentVersion');

Route::group(['middleware' => config('laraupdater.middleware') ], function(){
    Route::get('updater.update', 'salihkiraz\laraupdater\LaraUpdaterController@update');
});
