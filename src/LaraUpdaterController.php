<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/
namespace rohsyl\laraUpdater;

use GuzzleHttp\Client;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Artisan;
use Auth;
use rohsyl\laraupdater\Policies\ILaraUpdaterPolicy;
use ReflectionClass;

class LaraUpdaterController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $tmp_backup_dir = null;

    private function checkPermission(){

        $className = config('laraupdater.permissions.policy');

        if (!class_exists($className)) {
            // TODO: Error class not found
        }

        $ref = new ReflectionClass($className);
        $instance = $ref->newInstance();

        if (!$instance instanceof ILaraUpdaterPolicy) {
            // TODO: Error class must implements ILARAUPDATERPOLICY
        }

        return $instance->authorize(Auth::user());
    }

    /*
    * Download and Install Update.
    */
    public function update()
    {
        ignore_user_abort(true);
        header( 'Content-type: text/html; charset=utf-8' );

        if( ! $this->checkPermission() ){
            $this->println(trans("laraupdater.ACTION_NOT_ALLOWED."));
            return;
        }

        $lastVersionInfo = $this->getLastVersion();

        if ( $lastVersionInfo['version'] <= $this->getCurrentVersion() ){
            $this->println(trans("laraupdater.Your_System_IS_ALREADY_UPDATED_to_last version"));
            return;
        }

        try{
            $this->tmp_backup_dir = base_path().'/backup_'.date('Ymd');

            $this->println(trans("laraupdater.UPDATE_FOUND"));
            $this->println($lastVersionInfo['version'].' <i>('.trans("laraupdater.current_version").': '.$this->getCurrentVersion().')</i>');
            $this->println(trans("laraupdater.DESCRIPTION").': <i>'.$lastVersionInfo['description'].'</i>');

            $this->println(trans("laraupdater.Update_downloading_.."));

            $update_path = null;
            if( ($update_path = $this->download($lastVersionInfo)) === false)
                throw new \Exception(trans("laraupdater.Error_during_download."));

            $this->println(trans("laraupdater.OK"));

            Artisan::call('down');

            $this->println(trans("laraupdater.SYSTEM_Mantence_Mode").' => '.trans("laraupdater.ON"));

            if($this->install($lastVersionInfo['version'], $update_path, $lastVersionInfo['archive'])){
                $this->setCurrentVersion($lastVersionInfo['version']); //update system version

                Artisan::call('up'); //restore system UP status

                $this->println(trans("laraupdater.SYSTEM_Mantence_Mode").' => '.trans("laraupdater.OFF"));
                $this->println(trans("laraupdater.SYSTEM_IS_NOW_UPDATED_TO_VERSION").': '.$lastVersionInfo['version']);
            }else
                throw new \Exception(trans("laraupdater.Error_during_download."));

        }catch (\Exception $e) {

            $this->println(trans("laraupdater.ERROR_DURING_UPDATE_(!!check_the_update_archive!!)"));
            $this->println($e->getMessage());

            $this->restore();

            return;
        }
    }

    private function install($lastVersion, $update_path, $archive)
    {
        $currentVersion = $this->getCurrentVersion();
        try{
            $zipHandle = zip_open($update_path);

            while ($zip_item = zip_read($zipHandle) ){
                $filename = zip_entry_name($zip_item);
                $dirname = dirname($filename);

                $rootDirectory = substr($filename, 0, strlen(strtok($filename, '/')));

                // Exclude these cases (1/2)
                // Ignore /
                // Ignore __
                // Ignore $archive
                if(	substr($filename,-1,1) == '/'
                    || substr($dirname,0,2) === '__')
                    continue;

                // Ignore blacklist_directory
                if(in_array($dirname, config('laraupdater.blacklist_directory'))) {
                    continue;
                }

                // Exclude root folder
                $dirname = substr($dirname, strlen($rootDirectory));

                // Exclude these cases (2/2)
                // todo:check linux and windows test
                // if($dirname === '.' ) continue;

                $filename = $dirname.'/'.basename($filename); //set new purify path for current file

                if ( !is_dir(base_path().'/'.$dirname) ){ //Make NEW directory (if exist also in current version continue...)
                    File::makeDirectory(base_path().'/'.$dirname, $mode = 0755, true, true);
                    $this->println(trans("laraupdater.Directory").' => '.$dirname.'[ '.trans("laraupdater.OK").' ]');
                }

                if ( !is_dir(base_path().'/'.$filename) ){ //Overwrite a file with its last version
                    $contents = zip_entry_read($zip_item, zip_entry_filesize($zip_item));
                    $contents = str_replace("\r\n", "\n", $contents);


                    $this->print(trans("laraupdater.File").' => '.$filename.' ........... ');

                    // backup current version if it exists
                    if(File::exists(base_path().'/'.$filename)) {
                        $this->backup($filename);
                    }

                    File::put(base_path().'/'.$filename, $contents);

                    unset($contents);

                    $this->println(' [ '.trans("laraupdater.OK").' ]');
                }
            }
            zip_close($zipHandle);


            $upgradeScritPath = base_path() . '/' . config('laraupdater.post_upgrade_file_location');
            $this->println('Upgrade script path is : ' .$upgradeScritPath);
            if (file_exists($upgradeScritPath) && !is_dir($upgradeScritPath)){

                include($upgradeScritPath);

                if (function_exists('laraupdater_post_upgrade')) {
                    if (call_user_func('laraupdater_post_upgrade', $currentVersion, $lastVersion)) {
                        $this->println(trans("laraupdater.Commands_successfully_executed."));
                    }
                    else {
                        $this->println(trans("laraupdater.Error_during_commands_execution."));
                    }
                }
                else {
                    $this->println('Upgrade script ignored. (laraupdater_post_upgrade not exists in the file)');
                }
            }
            else {
                $this->println(trans('Upgrade script ignored. (not exists or not a file)'));
            }

            File::delete($update_path); //clean TMP
            File::deleteDirectory($this->tmp_backup_dir); //remove backup temp folder


        }catch (\Exception $e) {
            $this->println('Error during the install');
            $this->println($e->getMessage());
            return false;
        }

        return true;
    }

    /*
    * Download Update from $update_baseurl to $tmp_path (local folder).
    */
    private function download($update)
    {
        $update_name = $update['version'] . '.zip';
        $update_archive = $update['archive'];
        try{
            $download_directory = base_path(config('laraupdater.tmp_path'));
            $filename_tmp = $download_directory .'/'.$update_name;
            $this->println($filename_tmp);

            if (!file_exists($download_directory)) {
                mkdir($download_directory, 0777, true);
            }

            if ( !is_file( $filename_tmp ) ) {
                $client = new Client();
                $response = $client->request('GET', $update_archive);

                $dlHandler = fopen($filename_tmp, 'w');

                if ( !fwrite($dlHandler, $response->getBody()) ){
                    $this->println(trans("laraupdater.Could_not_save_new_update"));
                    exit();
                }
            }

        }catch (\Exception $e) {
            $this->println($e->getMessage());
            return false;
        }

        return $filename_tmp;
    }

    /*
    * Return current version (as plain text).
    */
    public function getCurrentVersion(){
        $version = File::get(base_path() . '/' . config('laraupdater.current_filename'));
        return $version;
    }

    /*
    * Check if a new Update exist.
    */
    public function check()
    {
        $lastVersionInfo = $this->getLastVersion();
        if( version_compare($lastVersionInfo['version'], $this->getCurrentVersion(), ">") )
            return $lastVersionInfo['version'];

        return '';
    }

    private function setCurrentVersion($last){
        File::put(base_path() . '/' . config('laraupdater.current_filename'), $last); //UPDATE $current_version to last version
    }

    private function getLastVersion(){
        $content = file_get_contents(config('laraupdater.update_baseurl').'/' . config('laraupdater.last_update_filename'));
        $content = json_decode($content, true);
        return $content; //['version' => $v, 'archive' => 'RELEASE-$v.zip', 'description' => 'plain text...'];
    }

    private function backup($filename){
        $backup_dir = $this->tmp_backup_dir;

        if ( !is_dir($backup_dir) ) File::makeDirectory($backup_dir, $mode = 0755, true, true);
        if ( !is_dir($backup_dir.'/'.dirname($filename)) ) File::makeDirectory($backup_dir.'/'.dirname($filename), $mode = 0755, true, true);

        File::copy(base_path().'/'.$filename, $backup_dir.'/'.$filename); //to backup folder
    }

    private function restore(){

        if( !isset($this->tmp_backup_dir) )
            $this->tmp_backup_dir = base_path().'/backup_'.date('Ymd');

        try {
            $backup_dir = $this->tmp_backup_dir;
            $backup_files = File::allFiles($backup_dir);

            foreach ($backup_files as $file) {
                $filename = (string)$file;
                $filename = substr($filename, (strlen($filename)-strlen($backup_dir)-1)*(-1));

                $this->println($backup_dir.'/'.$filename." => ".base_path().'/'.$filename);
                File::copy($backup_dir.'/'.$filename, base_path().'/'.$filename); //to respective folder
            }

            $this->println(trans("laraupdater.RESTORED"));

        } catch(\Exception $e) {

            $this->println(trans("laraupdater.FAILED"));

            $this->println($e->getMessage());
            $this->println(trans("laraupdater.Backup_folder_is_located_in:")." <i>".$backup_dir."</i>.");
            $this->println(trans("laraupdater.Remember_to_restore_System_UP-Status_through_shell_command:")." <i>php artisan up</i>.");

            return false;
        }

        return true;
    }


    private function print($text) {
        echo $text;
        flush();
        ob_flush();
    }
    private function println($text) {
        $this->print($text . '<br />');
    }
}
