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
		* Set which users can perform an update; 
		* This parameter accepts: ARRAY(user_id) ,or FALSE => for example: [1]  OR  [1,3,0]  OR  false
		* Generally, ADMIN have user_id=1; set FALSE to disable this check (not recommended)
		*/
		'allow_users_id' => [1] 
	];
