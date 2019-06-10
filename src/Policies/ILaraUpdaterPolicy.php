<?php
/**
 * Created by PhpStorm.
 * User: rohs
 * Date: 14.04.19
 * Time: 11:54
 */

namespace rohsyl\laraupdater\Policies;


interface ILaraUpdaterPolicy
{

    public function authorize($user);

}