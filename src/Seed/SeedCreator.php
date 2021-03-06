<?php

namespace Pisochek\LaravelSeeder\Seed;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class SeedCreator
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The registered post create hooks.
     *
     * @var array
     */
    protected $postCreate = [];

    /**
     * Create a new seed creator instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create a new seed at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     * @throws \Exception
     */
    public function create($name, $path)
    {
        $this->ensureSeedDoesntAlreadyExist($name);

        // First we will get the stub file for the seed, which serves as a type
        // of template for the seed. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->getStub();

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateStub($name, $stub)
        );

        // Next, we will fire any hooks that are supposed to fire after a seed is
        // created. Once that is done we'll be ready to return the full path to the
        // seed file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks();

        return $path;
    }

    /**
     * Ensure that a seed with the given name doesn't already exist.
     *
     * @param  string  $name
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function ensureSeedDoesntAlreadyExist($name)
    {
        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Get the seed stub file.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->files->get($this->stubPath() . '/blank.stub');
    }

    /**
     * Populate the place-holders in the seed stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @return string
     */
    protected function populateStub($name, $stub)
    {
        return str_replace('DummyClass', $this->getClassName($name), $stub);
    }

    /**
     * Get the class name of a seed name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the seed.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }

    /**
     * Fire the registered post create hooks.
     *
     * @return void
     */
    protected function firePostCreateHooks()
    {
        foreach ($this->postCreate as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Register a post seed create hook.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the date prefix for the seed.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
