<?php

namespace pcinaglia\laraupdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use pcinaglia\laraupdater\Helpers\UpdateHelper;
use Symfony\Component\Console\Input\InputArgument;

class CommandCurrentVersion extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laraupdater:current-version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Current version ('version.txt' in main folder) using laraupdater.";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updateHelper = new UpdateHelper();
        $currentVersion = $updateHelper->getCurrentVersion();
        $this->info($currentVersion);
        return 0;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
