<?php
/*
* @author: Pietro Cinaglia
* 	.website: http://linkedin.com/in/pietrocinaglia
*/
namespace pcinaglia\laraUpdater;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Artisan;
use Auth;

class LaraUpdaterController extends Controller
{

    private $tmp_backup_dir = null;

    private function checkPermission(){

        if( config('laraupdater.allow_users_id') !== null ){

            // 1
            if( config('laraupdater.allow_users_id') === false ) return true;

            // 2
            if( in_array(Auth::User()->id, config('laraupdater.allow_users_id')) === true ) return true;
        }

        return false;
    }
    /*
    * Download and Install Update.
    */
    public function update()
    {
        echo "<h2>".trans("laraupdater.LaraUpdater")."</h2>";
        echo '<h4><a href="'.url('/').'">'.trans("laraupdater.Return_to_App_HOME").'</a></h4>';

        if( ! $this->checkPermission() ){
            echo trans("laraupdater.ACTION_NOT_ALLOWED.");
            exit;
        }

        $lastVersionInfo = $this->getLastVersion();

        if ( $lastVersionInfo['version'] <= $this->getCurrentVersion() ){
            echo '<p>&raquo; '.trans("laraupdater.Your_System_IS_ALREADY_UPDATED_to_last version").' !</p>';
            exit;
        }

        try{
            $this->tmp_backup_dir = base_path().'/backup_'.date('Ymd');

            echo '<p>'.trans("laraupdater.UPDATE_FOUND").': '.$lastVersionInfo['version'].' <i>('.trans("laraupdater.current_version").': '.$this->getCurrentVersion().')</i></p>';
            echo '<p>'.trans("laraupdater.DESCRIPTION").': <i>'.$lastVersionInfo['description'].'</i></p>';
            echo '<p>&raquo; '.trans("laraupdater.Update_downloading_..").' ';

            $update_path = null;
            if( ($update_path = $this->download($lastVersionInfo['archive'])) === false)
                throw new \Exception(trans("laraupdater.Error_during_download."));

            echo trans("laraupdater.OK").' </p>';

            Artisan::call('down');
            echo '<p>&raquo; '.trans("laraupdater.SYSTEM_Mantence_Mode").' => '.trans("laraupdater.ON").'</p>';

            $status = $this->install($lastVersionInfo['version'], $update_path, $lastVersionInfo['archive']);

            if($status){
                $this->setCurrentVersion($lastVersionInfo['version']); //update system version
                Artisan::call('up'); //restore system UP status
                echo '<p>&raquo; '.trans("laraupdater.SYSTEM_Mantence_Mode").' => '.trans("laraupdater.OFF").'</p>';
                echo '<p class="success">'.trans("laraupdater.SYSTEM_IS_NOW_UPDATED_TO_VERSION").': '.$lastVersionInfo['version'].'</p>';
            }else
                throw new \Exception(trans("laraupdater.Error_during_download."));

        }catch (\Exception $e) {
            echo '<p>'.trans("laraupdater.ERROR_DURING_UPDATE_(!!check_the_update_archive!!)");

            $this->restore();

            echo '</p>';
        }
    }

    private function install($lastVersion, $update_path, $archive)
    {
        try{
            $execute_commands = false;
            $upgrade_cmds_filename = 'upgrade.php';
            $upgrade_cmds_path = config('laraupdater.tmp_path').'/'.$upgrade_cmds_filename;

            $zipHandle = zip_open($update_path);
            $archive = substr($archive,0, -4);

            echo '<p>'.trans("laraupdater.CHANGELOG").': </p>';
            echo '<ul>';

            while ($zip_item = zip_read($zipHandle) ){
                $filename = zip_entry_name($zip_item);
                $dirname = dirname($filename);

                // Exclude these cases (1/2)
                if(	substr($filename,-1,1) == '/' || dirname($filename) === $archive || substr($dirname,0,2) === '__') continue;

                //Exclude root folder (if exist)
                if( substr($dirname,0, strlen($archive)) === $archive )
                    $dirname = substr($dirname, (strlen($dirname)-strlen($archive)-1)*(-1));

                // Exclude these cases (2/2)
                // todo:check linux and windows test
                //if($dirname === '.' ) continue;

                $filename = $dirname.'/'.basename($filename); //set new purify path for current file

                if ( !is_dir(base_path().'/'.$dirname) ){ //Make NEW directory (if exist also in current version continue...)
                    File::makeDirectory(base_path().'/'.$dirname, $mode = 0755, true, true);
                    echo '<li>'.trans("laraupdater.Directory").' => '.$dirname.'[ '.trans("laraupdater.OK").' ]</li>';
                }

                if ( !is_dir(base_path().'/'.$filename) ){ //Overwrite a file with its last version
                    $contents = zip_entry_read($zip_item, zip_entry_filesize($zip_item));
                    $contents = str_replace("\r\n", "\n", $contents);

                    if ( strpos($filename, 'upgrade.php') !== false ) {
                        File::put($upgrade_cmds_path, $contents);
                        $execute_commands = true;

                    }else {
                        echo '<li>'.trans("laraupdater.File").' => '.$filename.' ........... ';

                        if(File::exists(base_path().'/'.$filename)) $this->backup($filename); //backup current version

                        File::put(base_path().'/'.$filename, $contents);
                        unset($contents);
                        echo' [ '.trans("laraupdater.OK").' ]'.'</li>';
                    }

                }
            }
            zip_close($zipHandle);
            echo '</ul>';

            if($execute_commands == true){
                include ($upgrade_cmds_path);

                if(main()) //upgrade-VERSION.php contains the 'main()' method with a BOOL return to check its execution.
                    echo '<p class="success">&raquo; '. trans("laraupdater.Commands_successfully_executed.") .'</p>';
                else
                    echo '<p class="danger">&raquo;'. trans("laraupdater.Error_during_commands_execution.") .'</p>';

                unlink($upgrade_cmds_path);
                File::delete($upgrade_cmds_path); //clean TMP
            }

            File::delete($update_path); //clean TMP
            File::deleteDirectory($this->tmp_backup_dir); //remove backup temp folder

        }catch (\Exception $e) { return false; }

        return true;
    }

    /*
    * Download Update from $update_baseurl to $tmp_path (local folder).
    */
    private function download($update_name)
    {
        try{
            $filename_tmp = config('laraupdater.tmp_path').'/'.$update_name;

            if ( !is_file( $filename_tmp ) ) {
                $newUpdate = file_get_contents(config('laraupdater.update_baseurl').'/'.$update_name);

                $dlHandler = fopen($filename_tmp, 'w');

                if ( !fwrite($dlHandler, $newUpdate) ){
                    echo '<p>'.trans("laraupdater.Could_not_save_new_update").'</p>';
                    exit();
                }
            }

        }catch (\Exception $e) { return false; }

        return $filename_tmp;
    }

    /*
    * Return current version (as plain text).
    */
    public function getCurrentVersion(){
        // todo: env file version
        $version = File::get(base_path().'/version.txt');
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
        File::put(base_path().'/version.txt', $last); //UPDATE $current_version to last version
    }

    private function getLastVersion(){
        $content = file_get_contents(config('laraupdater.update_baseurl').'/laraupdater.json');
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

        try{
            $backup_dir = $this->tmp_backup_dir;
            $backup_files = File::allFiles($backup_dir);

            foreach ($backup_files as $file){
                $filename = (string)$file;
                $filename = substr($filename, (strlen($filename)-strlen($backup_dir)-1)*(-1));
                echo $backup_dir.'/'.$filename." => ".base_path().'/'.$filename;
                File::copy($backup_dir.'/'.$filename, base_path().'/'.$filename); //to respective folder
            }

        }catch(\Exception $e) {
            echo "Exception => ".$e->getMessage();
            echo "<BR>[ ".trans("laraupdater.FAILED")." ]";
            echo "<BR> ".trans("laraupdater.Backup_folder_is_located_in:")." <i>".$backup_dir."</i>.";
            echo "<BR> ".trans("laraupdater.Remember_to_restore_System_UP-Status_through_shell_command:")." <i>php artisan up</i>.";
            return false;
        }

        echo "[ ".trans("laraupdater.RESTORED")." ]";
        return true;
    }
}
