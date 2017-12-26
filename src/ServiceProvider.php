<?php

namespace Pisochek\LaravelSeeder;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Pisochek\LaravelSeeder\Console\FreshCommand;
use Pisochek\LaravelSeeder\Console\InstallCommand;
use Pisochek\LaravelSeeder\Console\RefreshCommand;
use Pisochek\LaravelSeeder\Console\ResetCommand;
use Pisochek\LaravelSeeder\Console\RollbackCommand;
use Pisochek\LaravelSeeder\Console\SeedCommand;
use Pisochek\LaravelSeeder\Console\SeedMakeCommand;
use Pisochek\LaravelSeeder\Console\StatusCommand;
use Pisochek\LaravelSeeder\Seed\DatabaseSeedRepository;
use Pisochek\LaravelSeeder\Seed\SeedCreator;
use Pisochek\LaravelSeeder\Seed\Seeder;

class ServiceProvider extends IlluminateServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton('seed.repository', function ($app) {
            return new DatabaseSeedRepository($app['db'], $app['config']['database.seeds']);
        });

        $this->app->singleton('seeder', function ($app) {
            return new Seeder($app['seed.repository'], $app['db'], $app['files']);
        });

        $this->app->singleton('seed.creator', function ($app) {
            return new SeedCreator($app['files']);
        });

        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['seeder']);
        });

        $this->app->singleton('command.seed.fresh', function () {
            return new FreshCommand();
        });

        $this->app->singleton('command.seed.install', function ($app) {
            return new InstallCommand($app['seed.repository']);
        });

        $this->app->singleton('command.seed.refresh', function () {
            return new RefreshCommand();
        });

        $this->app->singleton('command.seed.reset', function ($app) {
            return new ResetCommand($app['seeder']);
        });

        $this->app->singleton('command.seed.rollback', function ($app) {
            return new RollbackCommand($app['seeder']);
        });

        $this->app->singleton('command.seed.make', function ($app) {
            return new SeedMakeCommand($app['seed.creator'], $app['composer'], $app['seeder']);
        });

        $this->app->singleton('command.seed.status', function ($app) {
            return new StatusCommand($app['seeder']);
        });

        $this->commands(
            'command.seed',
            'command.seed.fresh',
            'command.seed.install',
            'command.seed.refresh',
            'command.seed.reset',
            'command.seed.rollback',
            'command.seed.make',
            'command.seed.status'
        );
    }

    public function provides()
    {
        return [
            'seed.repository',
            'seeder',
            'seed.creator',
            'command.seed',
            'command.seed.fresh',
            'command.seed.install',
            'command.seed.refresh',
            'command.seed.reset',
            'command.seed.rollback',
            'command.seed.make',
            'command.seed.status'
        ];
    }
}
