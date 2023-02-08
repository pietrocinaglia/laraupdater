<?php

namespace pcinaglia\laraupdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use pcinaglia\laraupdater\Helpers\UpdateHelper;
use Symfony\Component\Console\Input\InputArgument;

class CommandUpdate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laraupdater:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update your application using laraupdater console command.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updateHelper = new UpdateHelper();
        $updateHelper->update();
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
