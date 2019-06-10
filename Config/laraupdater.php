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

        /* ********************
         * POST INSTALL SCRIPT
         * ********************
         * If this file exists in your app after the extraction of the archive and if
         * it contains a laraupdater_post_upgrade($currentVersion, $lastVersion) method it will be executed.
         */
        'post_upgrade_file_location' => 'update/update.php',

        /*
         * The name of the file in which the last version information are stored on your webserver
         */
        'last_update_filename' => 'current.json',

        /**
         * The name of the file that contains the current version
         * This file is located at the root of the project
         */
        'current_filename' => 'version',

		/*
		* Set a middleware for every routes
		* Only 'auth' NOT works (manage security using 'permissions' configuration)
		*/
		'middleware' => ['web', 'auth'],

        /*
         * Set of directory that will be ignored and not overwrited.
         */
        'blacklist_directory' => [
            /*
             * The config directory could be different in every instance of the app. That's why we ignore it
             */
            'config'
        ],

        'permissions' => [
            /**
             * Set which policy to check permissions
             * You can create your own by implementing the
             * rohsyl\laraupdater\Policies\ILaraUpdaterPolicy interface
             * and registering it here
             */
            'policy' => rohsyl\laraupdater\Policies\AllowUserIdLaraUpdaterPolicy::class,
            'parameters' => [
                /*
                 * This entry is related to the policy :
                 * rohsyl\laraupdater\Policies\AllowUserIdLaraUpdaterPolicy
                 *
                 * If you are not using this policy, you can remove it.
                 *
                 * Set which users can perform an update;
                 * This parameter accepts: ARRAY(user_id) ,or FALSE => for example: [1]  OR  [1,3,0]  OR  false
                 * Generally, ADMIN have user_id=1; set FALSE to disable this check (not recommended)
                 */
                'allow_users_id' => [1],
            ]
        ],
	];
