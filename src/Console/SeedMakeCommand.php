<?php

namespace Pisochek\LaravelSeeder\Console;

use Illuminate\Support\Composer;
use Pisochek\LaravelSeeder\Seed\SeedCreator;
use Pisochek\LaravelSeeder\Seed\Seeder;

class SeedMakeCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:aseed {name : The name of the seed.}
        {--path= : The location where the seed file should be created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seed file';

    /**
     * The seed creator instance.
     *
     * @var \Pisochek\LaravelSeeder\Seed\SeedCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new seed install command instance.
     *
     * @param \Pisochek\LaravelSeeder\Seed\SeedCreator $creator
     * @param \Illuminate\Support\Composer $composer
     * @param \Pisochek\LaravelSeeder\Seed\Seeder $seeder
     */
    public function __construct(SeedCreator $creator, Composer $composer, Seeder $seeder)
    {
        parent::__construct($seeder);

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = trim($this->input->getArgument('name'));

        $this->writeSeed($name);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the seed file to disk.
     *
     * @param  string  $name
     * @return string
     */
    protected function writeSeed($name)
    {
        $file = pathinfo($this->creator->create($name, $this->getSeedPath()), PATHINFO_FILENAME);

        $this->line("<info>Created Seed:</info> {$file}");
    }

    /**
     * Get seed path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getSeedPath()
    {
        if (!is_null($targetPath = $this->input->getOption('path'))) {
            return $this->laravel->basePath() . '/' . $targetPath;
        }

        return parent::getSeedPath();
    }
}
