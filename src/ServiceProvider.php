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
        $this->app->singleton('aseed.repository', function ($app) {
            return new DatabaseSeedRepository($app['db'], $app['config']['database.seeds']);
        });

        $this->app->singleton('aseeder', function ($app) {
            return new Seeder($app['aseed.repository'], $app['db'], $app['files']);
        });

        $this->app->singleton('aseed.creator', function ($app) {
            return new SeedCreator($app['files']);
        });

        $this->app->singleton('command.aseed', function ($app) {
            return new SeedCommand($app['aseeder']);
        });

        $this->app->singleton('command.aseed.fresh', function () {
            return new FreshCommand();
        });

        $this->app->singleton('command.aseed.install', function ($app) {
            return new InstallCommand($app['aseed.repository']);
        });

        $this->app->singleton('command.aseed.refresh', function () {
            return new RefreshCommand();
        });

        $this->app->singleton('command.aseed.reset', function ($app) {
            return new ResetCommand($app['aseeder']);
        });

        $this->app->singleton('command.aseed.rollback', function ($app) {
            return new RollbackCommand($app['aseeder']);
        });

        $this->app->singleton('command.aseed.make', function ($app) {
            return new SeedMakeCommand($app['aseed.creator'], $app['composer'], $app['aseeder']);
        });

        $this->app->singleton('command.aseed.status', function ($app) {
            return new StatusCommand($app['aseeder']);
        });

        $this->commands(
            'command.aseed',
            'command.aseed.fresh',
            'command.aseed.install',
            'command.aseed.refresh',
            'command.aseed.reset',
            'command.aseed.rollback',
            'command.aseed.make',
            'command.aseed.status'
        );
    }

    public function provides()
    {
        return [
            'aseed.repository',
            'aseeder',
            'aseed.creator',
            'command.aseed',
            'command.aseed.fresh',
            'command.aseed.install',
            'command.aseed.refresh',
            'command.aseed.reset',
            'command.aseed.rollback',
            'command.aseed.make',
            'command.aseed.status'
        ];
    }
}
