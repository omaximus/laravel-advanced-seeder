<?php

namespace Pisocheck\LaravelSeeder\Console;

use Illuminate\Console\ConfirmableTrait;

class SeedCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed {--database= : The database connection to use.}
                {--force : Force the operation to run when in production.}
                {--path= : The path of seed files to be executed.}
                {--pretend : Dump the SQL queries that would be run.}
                {--step : Force the seeds to be run so they can be rolled back individually.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeds';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        $this->seeder->run($this->getSeedPaths(), [
            'pretend' => $this->option('pretend'),
            'step' => $this->option('step'),
        ]);

        foreach ($this->seeder->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

    /**
     * Prepare the seed database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->seeder->setConnection($this->option('database'));

        if (!$this->seeder->repositoryExists()) {
            $this->call('seed:install', ['--database' => $this->option('database')]);
        }
    }
}
