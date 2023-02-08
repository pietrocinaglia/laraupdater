<?php
/*
* @author: Pietro Cinaglia
* https://github.com/pietrocinaglia
*/

namespace pcinaglia\laraUpdater;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Auth;
use pcinaglia\laraupdater\Helpers\UpdateHelper;

class LaraUpdaterController extends Controller
{
    private function checkPermission()
    {
        if (config('laraupdater.allow_users_id') !== null) {
            if (config('laraupdater.allow_users_id') === false || in_array(Auth::User()->id, config('laraupdater.allow_users_id')) === true) {
                return true;
            }
        }

        return false;
    }


    /*
    * Download and Install Update.
    */
    public function update()
    {
        $this->log(trans("laraupdater.SYSTEM_VERSION") . $this->getCurrentVersion(), true, 'info');

        if (!$this->checkPermission()) {
            $this->log(trans("laraupdater.PERMISSION_DENIED."), true, 'warn');
            return;
        }

        $updateHelper = new UpdateHelper();
        return $updateHelper->update();
    }

    /*
    * Check if a new Update exist.
    */
    public function check()
    {
        $updateHelper = new UpdateHelper();
        return $updateHelper->check();
    }

    /*
    * Current version ('version.txt' in main folder)
    */
    public function getCurrentVersion()
    {
        // todo: env file version
        $updateHelper = new UpdateHelper();
        return $updateHelper->getCurrentVersion();
    }
}
