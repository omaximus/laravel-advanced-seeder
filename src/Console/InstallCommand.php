<?php

namespace Pisochek\LaravelSeeder\Console;

use Illuminate\Console\Command;
use Pisochek\LaravelSeeder\Seed\SeedRepositoryInterface;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'seed:install';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create seed repository';

    /**
     * The repository instance.
     *
     * @var \Pisochek\LaravelSeeder\Seed\SeedRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new seed install command instance.
     *
     * @param  \Pisochek\LaravelSeeder\Seed\SeedRepositoryInterface  $repository
     */
    public function __construct(SeedRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->repository->setSource($this->input->getOption('database'));

        $this->repository->createRepository();

        $this->info('Seed table created successfully.');
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.']];
    }
}
