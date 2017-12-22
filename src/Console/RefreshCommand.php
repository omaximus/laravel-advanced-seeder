<?php

namespace Pisocheck\LaravelSeeder\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class RefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * {@inheritdoc}
     */
    protected $name = 'seed:refresh';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Reset and re-run all seeds';

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

        $database = $this->input->getOption('database');
        $path = $this->input->getOption('path');
        $force = $this->input->getOption('force');
        $step = $this->input->getOption('step') ?: 0;

        if ($step > 0) {
            $this->runRollback($database, $path, $step, $force);
        } else {
            $this->runReset($database, $path, $force);
        }

        $this->call('seed', ['--database' => $database, '--path' => $path, '--force' => $force]);
    }

    /**
     * Run the rollback command.
     *
     * @param  string  $database
     * @param  string  $path
     * @param  bool  $step
     * @param  bool  $force
     * @return void
     */
    protected function runRollback($database, $path, $step, $force)
    {
        $this->call('seed:rollback', [
            '--database' => $database,
            '--path' => $path,
            '--step' => $step,
            '--force' => $force,
        ]);
    }

    /**
     * Run the reset command.
     *
     * @param  string  $database
     * @param  string  $path
     * @param  bool  $force
     * @return void
     */
    protected function runReset($database, $path, $force)
    {
        $this->call('seed:reset', [
            '--database' => $database,
            '--path' => $path,
            '--force' => $force,
        ]);
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
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of seeds to be reverted & re-run.'],
        ];
    }
}
