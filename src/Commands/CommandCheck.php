<?php

namespace pcinaglia\laraupdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use pcinaglia\laraupdater\Helpers\UpdateHelper;
use Symfony\Component\Console\Input\InputArgument;

class CommandCheck extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laraupdater:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if a new Update exist using laraupdater.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updateHelper = new UpdateHelper();
        $check = $updateHelper->check();
        $this->info(json_encode($check));
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
