<?php

namespace Pisocheck\LaravelSeeder\Seed;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSeedRepository implements SeedRepositoryInterface
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the seed table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database seed repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
    }

    /**
     * Get the ran seeds.
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()
                    ->orderBy('batch', 'asc')
                    ->orderBy('seed', 'asc')
                    ->pluck('seed')->all();
    }

    /**
     * Get list of seeds.
     *
     * @param  int  $steps
     * @return array
     */
    public function getSeeds($steps)
    {
        $query = $this->table()->where('batch', '>=', '1');

        return $query->orderBy('batch', 'desc')
                     ->orderBy('seed', 'desc')
                     ->take($steps)
                     ->get()->all();
    }

    /**
     * Get the last seed batch.
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('seed', 'desc')->get()->all();
    }

    /**
     * Log that a seed was run.
     *
     * @param  string  $file
     * @param  int     $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['seed' => $file, 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Remove a seed from the log.
     *
     * @param  object  $seed
     * @return void
     */
    public function delete($seed)
    {
        $this->table()->where('seed', $seed->seed)->delete();
    }

    /**
     * Get the next seed batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last seed batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->max('batch');
    }

    /**
     * Create the seed repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function (Blueprint $table) {
            // The seeds table is responsible for keeping track of which of the
            // seeds have actually run for the application. We'll create the
            // table to hold the seed file's path as well as the batch ID.
            $table->increments('id');
            $table->string('seed');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the seed repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the seed table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}
