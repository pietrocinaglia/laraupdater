<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/

return [

	/*
    * Temp folder to store update before to install it.
	*/
	'tmp_path' => '/../tmp',

	/*
	* URL where your updates are stored ( e.g. for a folder named 'updates', under http://site.com/yourapp ).
	*/
	'update_baseurl' => 'http://site.com/yourapp/updates',

	/*
	* Set a middleware for the route: updater.update
	* Only 'auth' NOT works (manage security using 'allow_users_id' configuration)
	*/
	'middleware' => ['web', 'auth'],

	/*
	* Set the update check time period, to prevent update server overload.
	* Set the number in minutes
	*/
	'version_check_time' => 15,
];
