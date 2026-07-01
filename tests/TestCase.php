<?php

namespace Athwari\LaravelZktecoAdms\Tests;

use Athwari\LaravelZktecoAdms\LaravelZktecoAdmsServiceProvider;
use Athwari\LaravelZktecoAdms\Services\AttendanceParser;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * @method \Illuminate\Testing\TestResponse get(string $uri, array $headers = [])
 * @method \Illuminate\Testing\TestResponse post(string $uri, array $data = [], array $headers = [])
 * @method \Illuminate\Testing\TestResponse call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null)
 * @method \Illuminate\Testing\PendingCommand artisan(string $command, array $parameters = [])
 * @method $this assertDatabaseHas(string $table, array $data, string $connection = null)
 * @method $this assertDatabaseCount(string $table, int $count, string $connection = null)
 * @method $this assertDatabaseMissing(string $table, array $data, string $connection = null)
 */
abstract class TestCase extends BaseTestCase
{
    public AttendanceParser $parser;

    public DeviceManager $manager;

    public CommandManager $commandManager;

    public DeviceManager $deviceManager;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelZktecoAdmsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('zkteco-adms.device.max_devices', 100);
        $app['config']->set('zkteco-adms.device.auto_register', true);
        $app['config']->set('zkteco-adms.max_commands_per_device', 50);
        $app['config']->set('zkteco-adms.online_threshold', 120);
        $app['config']->set('zkteco-adms.default_timezone', 'UTC');
        $app['config']->set('zkteco-adms.storage_timezone', 'UTC');
        $app['config']->set('zkteco-adms.enable_inspect', false);
        $app['config']->set('zkteco-adms.events.dispatch_device_event', false);
        $app['config']->set('zkteco-adms.events.dispatch_attendance_received', true);
        $app['config']->set('zkteco-adms.events.dispatch_command_result', true);
        $app['config']->set('zkteco-adms.events.dispatch_device_registered', true);
        $app['config']->set('zkteco-adms.events.dispatch_device_connected', true);
        $app['config']->set('zkteco-adms.events.dispatch_user_synced', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $migrations = [
            'create_zkteco_devices_table.php.stub',
            'create_zkteco_users_table.php.stub',
            'create_zkteco_attendance_logs_table.php.stub',
            'create_zkteco_device_commands_table.php.stub',
            'create_zkteco_device_events_table.php.stub',
            'add_occurred_at_to_zkteco_attendance_logs_table.php.stub',
        ];

        foreach ($migrations as $migration) {
            $instance = require __DIR__.'/../database/migrations/'.$migration;
            $instance->up();
        }
    }

    /**
     * @param  string  $command
     * @param  array<string, mixed>  $parameters
     */
    public function artisan($command, $parameters = []): PendingCommand
    {
        return parent::artisan($command, $parameters);
    }
}
