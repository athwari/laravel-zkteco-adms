<?php

namespace Athwari\LaravelZktecoAdms;

use Athwari\LaravelZktecoAdms\Console\EvictStaleDevicesCommand;
use Athwari\LaravelZktecoAdms\Services\AttendanceParser;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceCommandBuilder;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelZktecoAdmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/zkteco-adms.php', 'zkteco-adms'
        );

        $this->registerServices();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/zkteco-adms.php' => config_path('zkteco-adms.php'),
            ], 'zkteco-adms-config');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->commands([
                EvictStaleDevicesCommand::class,
            ]);
        }

        $this->registerAdmsRoutes();
    }

    protected function registerAdmsRoutes(): void
    {
        $config = config('zkteco-adms.routes', []);

        $route = Route::prefix($config['prefix'] ?? 'iclock')
            ->middleware($config['middleware'] ?? []);

        if (! empty($config['domain'])) {
            $route->domain($config['domain']);
        }

        $route->group(__DIR__.'/../routes/adms.php');
    }

    protected function registerServices(): void
    {
        $this->app->singleton(AttendanceParser::class);
        $this->app->singleton(DeviceManager::class);
        $this->app->singleton(CommandManager::class);
        $this->app->singleton(DeviceCommandBuilder::class);
        $this->app->singleton(LaravelZktecoAdms::class);
    }
}
