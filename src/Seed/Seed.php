<?php

namespace Pisochek\LaravelSeeder\Seed;

abstract class Seed
{
    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Get the seed connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function checkUp()
    {
        return true;
    }

    public function checkDown()
    {
        return true;
    }
}
