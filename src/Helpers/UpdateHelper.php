<?php

namespace pcinaglia\laraupdater\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UpdateHelper
{
    private $tmp_backup_dir = null;
    private $response_html = '';


    private function initTmpBackupDir()
    {
        $this->tmp_backup_dir = storage_path('app/laraupdater') . '/backup_' . date('Ymd');
    }

    public function log($msg, $append_response = false, $type = 'info')
    {
        //Response HTML
        if ($append_response) {
            $this->response_html .= $msg . "<BR>";
        }
        //Log
        $header = "LaraUpdater - ";
        if ($type == 'info') {
            Log::info($header . '[info]' . $msg);
        } elseif ($type == 'warn') {
            Log::error($header . '[warn]' . $msg);
        } elseif ($type == 'err') {
            Log::error($header . '[err]' . $msg);
        } else {
            return;
        }

        if (app()->runningInConsole()) {
            dump($header . ' - ' . $msg);
        } else {
            echo ($header . ' - ' . $msg . "<br/>");
        }
    }

    /*
    * Download and Install Update.
    */
    public function update()
    {
        $this->log(trans("laraupdater.SYSTEM_VERSION") . $this->getCurrentVersion(), true, 'info');

        $last_version_info = $this->getLastVersion();
        $last_version = null;

        if ($last_version_info['version'] <= $this->getCurrentVersion()) {
            $this->log(trans("laraupdater.ALREADY_UPDATED"), true, 'info');
            return;
        }

        try {

            if (($last_version = $this->download($last_version_info['archive'])) === false) {
                return;
            }

            Artisan::call('down'); // Maintenance mode ON
            $this->log(trans("laraupdater.MAINTENANCE_MODE_ON"), true, 'info');

            if (($status = $this->install($last_version)) === false) {
                $this->log(trans("laraupdater.INSTALLATION_ERROR"), true, 'err');
                return;
            }

            $this->setCurrentVersion($last_version_info['version']); //update system version
            $this->log(trans("laraupdater.INSTALLATION_SUCCESS"), true, 'info');

            $this->log(trans("laraupdater.SYSTEM_VERSION") . $this->getCurrentVersion(), true, 'info');

            Artisan::call('up'); // Maintenance mode OFF
            $this->log(trans("laraupdater.MAINTENANCE_MODE_OFF"), true, 'info');
        } catch (\Exception $e) {
            $this->log(trans("laraupdater.EXCEPTION") . '<small>' . $e->getMessage() . '</small>', true, 'err');
            $this->recovery();

            // up laravel after recovery on error
            Artisan::call('up');
        }
    }

    private function install($archive)
    {
        try {
            $execute_commands = false;
            $update_script = base_path() . '/' . config('laraupdater.tmp_folder_name') . '/' . config('laraupdater.script_filename');


            $zip = new \ZipArchive();
            if ($zip->open($archive)) {
                $archive = substr($archive, 0, -4);

                // check if upgrade_scipr exist
                $update_script_content = $zip->getFromName(config('laraupdater.script_filename'));
                print($update_script_content);
                if ($update_script_content) {
                    File::put($update_script, $update_script_content);

                    // include update script;
                    include_once $update_script;
                    $execute_commands = true;

                    // run beforeUpdate function from update script
                    beforeUpdate();
                }


                $this->log(trans("laraupdater.CHANGELOG"), true, 'info');


                for ($indexFile = 0; $indexFile < $zip->numFiles; $indexFile++) {
                    $filename = $zip->getNameIndex($indexFile);
                    $dirname = dirname($filename);

                    // Exclude files

                    if (substr($filename, -1, 1) == '/' || dirname($filename) === $archive || substr($dirname, 0, 2) === '__') {
                        continue;
                    }

                    if (strpos($filename, 'version.txt') !== false) {
                        continue;
                    }

                    if (substr($dirname, 0, strlen($archive)) === $archive) {
                        $dirname = substr($dirname, (strlen($dirname) - strlen($archive) - 1) * (-1));
                    };


                    $filename = $dirname . '/' . basename($filename); //set new purify path for current file

                    if (!is_dir(base_path() . '/' . $dirname)) { //Make NEW directory (if exist also in current version continue...)
                        File::makeDirectory(base_path() . '/' . $dirname, 0755, true, true);
                        $this->log(trans("laraupdater.DIRECTORY_CREATED") . $dirname, true, 'info');
                    }

                    if (!is_dir(base_path() . '/' . $filename)) { //Overwrite a file with its last version
                        $contents = $zip->getFromIndex($indexFile);
                        $contents = str_replace("\r\n", "\n", $contents);
                        if (File::exists(base_path() . '/' . $filename)) {
                            $this->log(trans("laraupdater.FILE_EXIST") . $filename, true, 'info');
                            $this->backup($filename); //backup current version
                        }

                        $this->log(trans("laraupdater.FILE_COPIED") . $filename, true, 'info');

                        File::put(base_path() . '/' . $filename, $contents);
                        unset($contents);
                    }
                }
                $zip->close();

                if ($execute_commands == true) {
                    // upgrade-VERSION.php contains the 'main()' method with a BOOL return to check its execution.
                    afterUpdate();
                    unlink($update_script);
                    $this->log(trans("laraupdater.EXECUTE_UPDATE_SCRIPT") . ' (\'upgrade.php\')', true, 'info');
                }

                File::delete($archive);
                File::deleteDirectory($this->tmp_backup_dir);
                $this->log(trans("laraupdater.TEMP_CLEANED"), true, 'info');
            }
        } catch (\Exception $e) {
            $this->log(trans("laraupdater.EXCEPTION") . '<small>' . $e->getMessage() . '</small>', true, 'err');
            return false;
        }

        return true;
    }

    /*
    * Download Update from $update_baseurl to $tmp_folder_name (local folder).
    */
    private function download($filename)
    {
        $this->log(trans("laraupdater.DOWNLOADING"), true, 'info');

        $tmp_folder_name = base_path() . '/' . config('laraupdater.tmp_folder_name');

        if (!is_dir($tmp_folder_name)) {
            File::makeDirectory($tmp_folder_name, $mode = 0755, true, true);
        }

        try {
            $local_file = $tmp_folder_name . '/' . $filename;
            $remote_file_url = config('laraupdater.update_baseurl') . '/' . $filename;

            $update = file_get_contents($remote_file_url);
            file_put_contents($local_file, $update);
        } catch (\Exception $e) {
            $this->log(trans("laraupdater.DOWNLOADING_ERROR"), true, 'err');
            $this->log(trans("laraupdater.EXCEPTION") . '<small>' . $e->getMessage() . '</small>', true, 'err');
            return false;
        }

        $this->log(trans("laraupdater.DOWNLOADING_SUCCESS"), true, 'info');
        return $local_file;
    }

    /*
    * Current version ('version.txt' in main folder)
    */
    public function getCurrentVersion()
    {
        // todo: env file version
        $version = File::get(base_path() . '/version.txt');
        return $version;
    }
    private function setCurrentVersion($version)
    {
        // todo: env file version
        File::put(base_path() . '/version.txt', $version);
    }

    /*
    * Check if a new Update exist.
    */
    public function check()
    {
        $last_version = $this->getLastVersion();
        if (version_compare($last_version['version'], $this->getCurrentVersion(), ">")) {
            return $last_version['version'];
        }
        return '';
    }

    private function getLastVersion()
    {
        $last_version = file_get_contents(config('laraupdater.update_baseurl') . '/laraupdater.json');
        $last_version = json_decode($last_version, true);
        // $last_version : ['version' => $v, 'archive' => 'RELEASE-$v.zip', 'description' => 'plainText'];
        return $last_version;
    }

    /*
    * Backup files before performing the update.
    */
    private function backup($filename)
    {
        if (!isset($this->tmp_backup_dir)) {
            $this->initTmpBackupDir();
        }

        $backup_dir = $this->tmp_backup_dir;
        if (!is_dir($backup_dir)) {
            File::makeDirectory($backup_dir, $mode = 0755, true, true);
        }

        if (!is_dir($backup_dir . '/' . dirname($filename))) {
            File::makeDirectory($backup_dir . '/' . dirname($filename), $mode = 0755, true, true);
        }

        File::copy(base_path() . '/' . $filename, $backup_dir . '/' . $filename); //to backup folder
    }

    /*
    * Recovery system from the last backup.
    */
    private function recovery()
    {
        $this->log(trans("laraupdater.RECOVERY") . '<small>' . $e . '</small>', true, 'info');

        if (!isset($this->tmp_backup_dir)) {;
            $this->initTmpBackupDir();
            $this->log(trans("laraupdater.BACKUP_FOUND") . '<small>' . $this->tmp_backup_dir . '</small>', true, 'info');
        }

        try {
            $backup_dir = $this->tmp_backup_dir;
            $backup_files = File::allFiles($backup_dir);
            foreach ($backup_files as $file) {
                $filename = (string)$file;
                $filename = substr($filename, (strlen($filename) - strlen($backup_dir) - 1) * (-1));
                File::copy($backup_dir . '/' . $filename, base_path() . '/' . $filename); //to respective folder
            }
        } catch (\Exception $e) {
            $this->log(trans("laraupdater.RECOVERY_ERROR"), true, 'err');
            $this->log(trans("laraupdater.EXCEPTION") . '<small>' . $e->getMessage() . '</small>', true, 'err');
            return false;
        }

        $this->log(trans("laraupdater.RECOVERY_SUCCESS"), true, 'info');
        return true;
    }
}
