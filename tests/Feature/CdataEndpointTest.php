<?php

use Athwari\LaravelZktecoAdms\Events\AttendanceReceived;
use Athwari\LaravelZktecoAdms\Events\DeviceInfoReceived;
use Athwari\LaravelZktecoAdms\Events\UserQueryReceived;
use Athwari\LaravelZktecoAdms\Events\UserSynced;
use Athwari\LaravelZktecoAdms\Exceptions\InvalidSerialNumberException;
use Athwari\LaravelZktecoAdms\Models\ZktecoAttendanceLog;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use Athwari\LaravelZktecoAdms\Tests\TestCase;
use Illuminate\Support\Facades\Event;

test('cdata get handshake', function () {
    /** @var TestCase $this */
    $response = $this->get('/iclock/cdata?SN=TEST001');

    $response->assertStatus(200);
    $response->assertSee('OK');
});

test('cdata missing sn returns 400', function () {
    /** @var TestCase $this */
    $this->get('/iclock/cdata')->assertStatus(400);
});

test('cdata invalid sn returns 400', function () {
    /** @var TestCase $this */
    $this->get('/iclock/cdata?SN=has%20space')->assertStatus(400);
});

test('cdata handles serial numbers rejected during registration', function () {
    /** @var TestCase $this */
    $manager = Mockery::mock(DeviceManager::class);
    $manager->shouldReceive('registerDevice')
        ->once()
        ->andThrow(new InvalidSerialNumberException('TEST001', 'rejected'));
    app()->instance(DeviceManager::class, $manager);

    $this->get('/iclock/cdata?SN=TEST001')->assertStatus(400);
});

test('cdata post attlog', function () {
    /** @var TestCase $this */
    Event::fake([AttendanceReceived::class]);

    $body = "1001\t2024-03-15 08:30:00\t0\t1\t\n1002\t2024-03-15 08:31:00\t1\t4\tWC01";

    $response = $this->call('POST', '/iclock/cdata?SN=TEST001&table=ATTLOG', [], [], [], [], $body);

    $response->assertStatus(200);
    $response->assertSee('OK: 2');

    $table = (new ZktecoAttendanceLog())->getTable();

    $this->assertDatabaseCount($table, 2);
    $this->assertDatabaseHas($table, [
        'pin' => '1001',
        'status' => 0,
    ]);

    Event::assertDispatched(AttendanceReceived::class, fn ($event) => $event->serialNumber === 'TEST001' && count($event->records) === 2);
});

test('cdata stores device-local and normalized attendance timestamps', function () {
    /** @var TestCase $this */
    config()->set('zkteco-adms.default_timezone', 'Asia/Aden');
    config()->set('zkteco-adms.storage_timezone', 'UTC');

    $body = "1001\t2024-03-15 08:30:00\t0\t1\t";
    $this->call('POST', '/iclock/cdata?SN=TIMEZONE001&table=ATTLOG', [], [], [], [], $body)
        ->assertOk();

    $attendance = ZktecoAttendanceLog::query()->firstOrFail();

    expect($attendance->recorded_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 08:30:00')
        ->and($attendance->occurred_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 05:30:00');
});

test('cdata honors a configured attendance storage timezone', function () {
    /** @var TestCase $this */
    config()->set('zkteco-adms.default_timezone', 'Asia/Aden');
    config()->set('zkteco-adms.storage_timezone', 'America/New_York');

    $body = "1001\t2024-03-15 08:30:00\t0\t1\t";
    $this->call('POST', '/iclock/cdata?SN=TIMEZONE002&table=ATTLOG', [], [], [], [], $body)
        ->assertOk();

    $attendance = ZktecoAttendanceLog::query()->firstOrFail();

    expect($attendance->recorded_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 08:30:00')
        ->and($attendance->occurred_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 01:30:00');
});

test('cdata falls back to utc for an invalid attendance storage timezone', function () {
    /** @var TestCase $this */
    config()->set('zkteco-adms.default_timezone', 'Asia/Aden');
    config()->set('zkteco-adms.storage_timezone', 'Mars/Phobos');

    $body = "1001\t2024-03-15 08:30:00\t0\t1\t";
    $this->call('POST', '/iclock/cdata?SN=TIMEZONE003&table=ATTLOG', [], [], [], [], $body)
        ->assertOk();

    $attendance = ZktecoAttendanceLog::query()->firstOrFail();

    expect($attendance->recorded_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 08:30:00')
        ->and($attendance->occurred_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 05:30:00');
});

test('cdata records attendance device events when enabled', function () {
    /** @var TestCase $this */
    config()->set('zkteco-adms.events.dispatch_device_event', true);

    $body = "1001\t2024-03-15 08:30:00\t0\t1\t";
    $this->call('POST', '/iclock/cdata?SN=EVENTATT001&table=ATTLOG', [], [], [], [], $body)
        ->assertOk();

    expect(ZktecoDeviceEvent::query()
        ->where('event_type', 'attendance_synced')
        ->exists())->toBeTrue();
});

test('cdata post operlog processes user records', function () {
    /** @var TestCase $this */
    $body = "USER PIN=1001\tName=John Doe\tPri=0\tCard=12345";
    $response = $this->call('POST', '/iclock/cdata?SN=TEST001&table=OPERLOG', [], [], [], [], $body);

    $response->assertStatus(200);
    $response->assertSee('OK');
});

test('cdata operlog dispatches user and device events when enabled', function () {
    /** @var TestCase $this */
    Event::fake([UserSynced::class]);
    config()->set('zkteco-adms.events.dispatch_device_event', true);

    $body = "USER PIN=1001\tName=John Doe\tPri=0\tCard=12345";
    $this->call('POST', '/iclock/cdata?SN=EVENTUSER001&table=OPERLOG', [], [], [], [], $body)
        ->assertOk();

    Event::assertDispatched(UserSynced::class);
    expect(ZktecoDeviceEvent::query()
        ->where('event_type', 'user_synced')
        ->exists())->toBeTrue();
});

test('cdata post device info', function () {
    /** @var TestCase $this */
    Event::fake([DeviceInfoReceived::class]);

    $body = "FWVersion=Ver 8.1.1\nDeviceName=TestDevice\nIPAddress=192.168.1.100";

    $response = $this->call('POST', '/iclock/cdata?SN=TEST001', [], [], [], [], $body);

    $response->assertStatus(200);

    Event::assertDispatched(DeviceInfoReceived::class, fn ($event) => $event->serialNumber === 'TEST001'
        && $event->info['FWVersion'] === 'Ver 8.1.1');
});

test('cdata post userinfo dispatches query event', function () {
    /** @var TestCase $this */
    Event::fake([UserQueryReceived::class]);

    $body = "PIN=1001\tName=John Doe\tPri=14\tCard=12345\tPasswd=secret";

    $response = $this->call('POST', '/iclock/cdata?SN=TEST001&table=USERINFO', [], [], [], [], $body);

    $response->assertStatus(200);
    $response->assertSee('OK');

    Event::assertDispatched(UserQueryReceived::class, fn ($event) => $event->serialNumber === 'TEST001'
        && count($event->users) === 1
        && $event->users[0]->pin === '1001');
});

test('cdata default path writes pending commands after processing device info', function () {
    /** @var TestCase $this */
    $this->get('/iclock/cdata?SN=TEST001')->assertStatus(200);

    $commandManager = app(CommandManager::class);
    $commandManager->sendInfoCommand('TEST001');

    $body = "FWVersion=Ver 8.1.1\nDeviceName=TestDevice";

    $response = $this->call('POST', '/iclock/cdata?SN=TEST001', [], [], [], [], $body);

    $response->assertStatus(200);

    expect($response->getContent())
        ->toContain('C:')
        ->toContain('INFO');
});

test('cdata device limit reached', function () {
    /** @var TestCase $this */
    config()->set('zkteco-adms.device.max_devices', 1);

    $this->get('/iclock/cdata?SN=DEV001');

    $this->get('/iclock/cdata?SN=DEV002')->assertStatus(503);
});

test('cdata auto register disabled returns 403', function () {
    /** @var TestCase $this */
    config()->set('zkteco-adms.device.auto_register', false);

    $this->get('/iclock/cdata?SN=NEWDEV001')->assertStatus(403);
});

test('cdata parses query parameters on connection', function () {
    /** @var TestCase $this */
    $url = '/iclock/cdata?DeviceType=middle%20east&SN=QQD4253600095&language=69&options=all&pushver=2.4.1&FirmwareVersion=12';

    $response = $this->get($url);
    $response->assertStatus(200);

    $deviceModel = config('zkteco-adms.models.device', ZktecoDevice::class);
    $device = $deviceModel::where('serial_number', 'QQD4253600095')->first();

    expect($device)->not->toBeNull();
    expect($device->name)->toBe('Device QQD4253600095');
    expect($device->device_type)->toBe('middle east');
    expect($device->language)->toBe('69');
    expect($device->push_version)->toBe('2.4.1');
    expect($device->firmware_version)->toBe('12');
});
