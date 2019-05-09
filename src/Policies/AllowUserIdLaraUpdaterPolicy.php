<?php
/**
 * Created by PhpStorm.
 * User: rohs
 * Date: 14.04.19
 * Time: 11:55
 */

namespace rohsyl\laraupdater\Policies;


class AllowUserIdLaraUpdaterPolicy implements ILaraUpdaterPolicy
{

    public function authorize($user)
    {

        if( config('laraupdater.permissions.parameters.allow_users_id') !== null ){

            // 1
            if( config('laraupdater.permissions.parameters.allow_users_id') === false )
                return true;

            // 2
            if( in_array($user->id, config('laraupdater.permissions.parameters.allow_users_id')) === true )
                return true;
        }

        return false;
    }
}