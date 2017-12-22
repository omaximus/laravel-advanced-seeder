<?php

namespace Pisocheck\LaravelSeeder\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class FreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * {@inheritdoc}
     */
    protected $name = 'seed:fresh';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Truncated all tables and re-run all seeds';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->truncateAllTables($database = $this->input->getOption('database'));

        $this->info('Truncated all tables successfully.');

        $this->call('seed', [
            '--database' => $database,
            '--path' => $this->input->getOption('path'),
            '--force' => true,
        ]);
    }

    /**
     * Truncate all of the database tables.
     *
     * @param  string  $database
     * @return void
     */
    protected function truncateAllTables($database)
    {
        $tables = $this->laravel['db']->connection($database)
                            ->getSchemaBuilder()
                            ->getAllTables();

        foreach ($tables as $table) {
            $this->laravel['db']->connection($database)
                                ->table($table)
                                ->truncate();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The path of seeds files to be executed.'],
        ];
    }
}
