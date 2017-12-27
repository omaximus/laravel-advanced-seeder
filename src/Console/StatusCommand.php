<?php

namespace Pisochek\LaravelSeeder\Console;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'aseed:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each seed';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->seeder->setConnection($this->option('database'));

        if (!$this->seeder->repositoryExists()) {
            $this->error('No seeds found.');

            return;
        }

        $ran = $this->seeder->getRepository()->getRan();

        if (count($seeds = $this->getStatusFor($ran)) > 0) {
            $this->table(['Ran?', 'Seed'], $seeds);
        } else {
            $this->error('No seeds found');
        }
    }

    /**
     * Get the status for the given ran seeds.
     *
     * @param  array  $ran
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran)
    {
        return Collection::make($this->getAllSeedFiles())
                    ->map(function ($seed) use ($ran) {
                        $seedName = $this->seeder->getSeedName($seed);

                        return in_array($seedName, $ran)
                                ? ['<info>Y</info>', $seedName]
                                : ['<fg=red>N</fg=red>', $seedName];
                    });
    }

    /**
     * Get an array of all of the seed files.
     *
     * @return array
     */
    protected function getAllSeedFiles()
    {
        return $this->seeder->getSeedFiles($this->getSeedPaths());
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The path of seeds files to use.'],
        ];
    }
}
