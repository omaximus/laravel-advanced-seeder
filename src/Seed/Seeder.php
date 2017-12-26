<?php

namespace Pisochek\LaravelSeeder\Seed;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class Seeder
{
    /**
     * The seed repository implementation.
     *
     * @var \Pisochek\LaravelSeeder\Seed\SeedRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The paths to all of the seed files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Create a new seeder instance.
     *
     * @param  \Pisochek\LaravelSeeder\Seed\SeedRepositoryInterface  $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(SeedRepositoryInterface $repository, Resolver $resolver, Filesystem $files)
    {
        $this->files = $files;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the pending seeds at a given path.
     *
     * @param  array|string  $paths
     * @param  array  $options
     *
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];
        $files = $this->getSeedFiles($paths);
        $seeds = $this->pendingSeeds($files, $this->repository->getRan());

        $this->requireFiles($seeds);

        $this->runPending($seeds, $options);

        return $seeds;
    }

    /**
     * Get the seed files that have not yet run.
     *
     * @param  array  $files
     * @param  array  $ran
     *
     * @return array
     */
    protected function pendingSeeds($files, $ran)
    {
        return Collection::make($files)
                         ->reject(function ($file) use ($ran) {
                             return in_array($this->getSeedName($file), $ran);
                         })
                         ->values()
                         ->all();
    }

    /**
     * Run an array of seeds.
     *
     * @param  array  $seeds
     * @param  array  $options
     *
     * @return void
     */
    public function runPending(array $seeds, array $options = [])
    {
        if (count($seeds) == 0) {
            $this->note('<info>Nothing to seed.</info>');

            return;
        }

        $batch = $this->repository->getNextBatchNumber();
        $pretend = $options['pretend'] ?? false;
        $step = $options['step'] ?? false;

        foreach ($seeds as $file) {
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * Run "up" a seed instance.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  bool    $pretend
     *
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        $name = $this->getSeedName($file);
        $seed = $this->resolve($name);

        if ($pretend) {
            $this->pretendToRun($seed, 'up');

            return;
        }

        $this->note("<comment>Seeding:</comment> {$name}");

        if ($seed->checkUp()) {
            $this->runSeed($seed, 'up');
            $this->repository->log($name, $batch);
            $this->note("<info>Seeded:</info>  {$name}");
        } else {
            $this->note("<fg=red>Seed:</> {$name} can't be run");
        }
    }

    /**
     * Rollback the last seed operation.
     *
     * @param  array|string $paths
     * @param  array  $options
     *
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];
        $seeds = $this->getSeedsForRollback($options);

        if (count($seeds) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->rollbackSeeds($seeds, $paths, $options);
    }

    /**
     * Get the seeds for a rollback operation.
     *
     * @param  array  $options
     *
     * @return array
     */
    protected function getSeedsForRollback(array $options)
    {
        if (($steps = $options['step'] ?? 0) > 0) {
            return $this->repository->getSeeds($steps);
        } else {
            return $this->repository->getLast();
        }
    }

    /**
     * Rollback the given seeds.
     *
     * @param  array  $seeds
     * @param  array|string  $paths
     * @param  array  $options
     *
     * @return array
     */
    protected function rollbackSeeds(array $seeds, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getSeedFiles($paths));

        foreach ($seeds as $seed) {
            $seed = (object)$seed;

            if (!$file = Arr::get($files, $seed->seed)) {
                $this->note("<fg=red>Seed not found:</> {$seed->seed}");

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown($file, $seed, $options['pretend'] ?? false);
        }

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied seeds back.
     *
     * @param  array|string $paths
     * @param  bool  $pretend
     *
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        $this->notes = [];
        $seeds = array_reverse($this->repository->getRan());

        if (count($seeds) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->resetSeeds($seeds, $paths, $pretend);
    }

    /**
     * Reset the given seeds.
     *
     * @param  array  $seeds
     * @param  array  $paths
     * @param  bool  $pretend
     *
     * @return array
     */
    protected function resetSeeds(array $seeds, array $paths, $pretend = false)
    {
        $seeds = collect($seeds)->map(function ($m) {
            return (object) ['seed' => $m];
        })->all();

        return $this->rollbackSeeds($seeds, $paths, ['pretend' => $pretend]);
    }

    /**
     * Run "down" a seed instance.
     *
     * @param  string  $file
     * @param  object  $seed
     * @param  bool    $pretend
     *
     * @return void
     */
    protected function runDown($file, $seed, $pretend)
    {
        $name = $this->getSeedName($file);
        $instance = $this->resolve($name);

        $this->note("<comment>Rolling back:</comment> {$name}");

        if ($pretend) {
            $this->pretendToRun($instance, 'down');

            return;
        }

        if ($instance->checkDown()) {
            $this->runSeed($instance, 'down');
            $this->repository->delete($seed);
            $this->note("<info>Rolled back:</info>  {$name}");
        } else {
            $this->note("<fg=red>Seed:</> {$name} can't be rolled back");
        }
    }

    /**
     * Run a seed inside a transaction if the database supports it.
     *
     * @param  \Pisochek\LaravelSeeder\Seed\Seed  $seed
     * @param  string  $method
     *
     * @return void
     */
    protected function runSeed($seed, $method)
    {
        $connection = $this->resolveConnection($seed->getConnection());

        $callback = function () use ($seed, $method) {
            if (method_exists($seed, $method)) {
                $seed->{$method}();
            }
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions()
                    ? $connection->transaction($callback)
                    : $callback();
    }

    /**
     * Pretend to run the seeds.
     *
     * @param  object  $seed
     * @param  string  $method
     *
     * @return void
     */
    protected function pretendToRun($seed, $method)
    {
        foreach ($this->getQueries($seed, $method) as $query) {
            $name = get_class($seed);

            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     * Get all of the queries that would be run for a seed.
     *
     * @param  object  $seed
     * @param  string  $method
     *
     * @return array
     */
    protected function getQueries($seed, $method)
    {
        $db = $this->resolveConnection($seed->getConnection());

        return $db->pretend(function () use ($seed, $method) {
            if (method_exists($seed, $method)) {
                $seed->{$method}();
            }
        });
    }

    /**
     * Resolve a seed instance from a file.
     *
     * @param  string  $file
     *
     * @return \Pisochek\LaravelSeeder\Seed\Seed
     */
    public function resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Get all of the seed files in a given path.
     *
     * @param  string|array  $paths
     *
     * @return array
     */
    public function getSeedFiles($paths)
    {
        return Collection::make($paths)
                         ->flatMap(function ($path) {
                             return $this->files->glob($path . '/*_*.php');
                         })
                         ->filter()
                         ->sortBy(function ($file) {
                             return $this->getSeedName($file);
                         })
                         ->values()
                         ->keyBy(function ($file) {
                             return $this->getSeedName($file);
                         })
                         ->all();
    }

    /**
     * Require in all the seed files in a given path.
     *
     * @param  array   $files
     *
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the seed.
     *
     * @param  string  $path
     *
     * @return string
     */
    public function getSeedName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom seed path.
     *
     * @param  string  $path
     *
     * @return void
     */
    public function path($path)
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom seed paths.
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setConnection($name)
    {
        if (! is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param  string  $connection
     *
     * @return \Illuminate\Database\Connection
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Get the schema grammar out of a seed connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getSchemaGrammar($connection)
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Get the seed repository instance.
     *
     * @return \Pisochek\LaravelSeeder\Seed\SeedRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the seed repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Raise a note event for the seeder.
     *
     * @param  string  $message
     *
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
