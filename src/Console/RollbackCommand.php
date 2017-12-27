<?php

namespace Pisochek\LaravelSeeder\Console;

use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'aseed:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database seed';

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

        $this->seeder->setConnection($this->option('database'));

        $this->seeder->rollback(
            $this->getSeedPaths(), [
                'pretend' => $this->option('pretend'),
                'step' => (int) $this->option('step'),
            ]
        );

        foreach ($this->seeder->getNotes() as $note) {
            $this->output->writeln($note);
        }
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
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The path of seeds files to be executed.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of seeds to be reverted.'],
        ];
    }
}
