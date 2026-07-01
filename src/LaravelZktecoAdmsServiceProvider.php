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

            $timestamp = time();
            $migrations = [
                'create_zkteco_devices_table.php.stub',
                'create_zkteco_users_table.php.stub',
                'create_zkteco_attendance_logs_table.php.stub',
                'create_zkteco_device_commands_table.php.stub',
                'create_zkteco_device_events_table.php.stub',
                'add_occurred_at_to_zkteco_attendance_logs_table.php.stub',
            ];
            $migrationPaths = [];

            foreach ($migrations as $offset => $migration) {
                $migrationPaths[__DIR__.'/../database/migrations/'.$migration] = database_path(
                    'migrations/'.date('Y_m_d_His', $timestamp + $offset).'_'.str_replace('.stub', '', $migration)
                );
            }

            $this->publishes($migrationPaths, 'zkteco-adms-migrations');

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
