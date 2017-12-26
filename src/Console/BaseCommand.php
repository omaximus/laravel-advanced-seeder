<?php

namespace Pisochek\LaravelSeeder\Console;

use Illuminate\Console\Command;
use Pisochek\LaravelSeeder\Seed\Seeder;

class BaseCommand extends Command
{
    protected $seeder;

    public function __construct(Seeder $seeder)
    {
        parent::__construct();

        $this->seeder = $seeder;
    }

    /**
     * Get all of the seed paths.
     *
     * @return array
     */
    protected function getSeedPaths()
    {
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return $this->laravel->basePath() . '/' . $path;
            })->all();
        }

        return array_merge([$this->getSeedPath()], $this->seeder->paths());
    }

    /**
     * Get the path to the seed directory.
     *
     * @return string
     */
    protected function getSeedPath()
    {
        return $this->laravel->databasePath() . DIRECTORY_SEPARATOR . 'advanced-seeds';
    }
}
