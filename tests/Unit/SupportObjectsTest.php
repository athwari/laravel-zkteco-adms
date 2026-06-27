<?php

use Athwari\LaravelZktecoAdms\DTOs\AttendanceRecord;
use Athwari\LaravelZktecoAdms\DTOs\CommandEntry;
use Athwari\LaravelZktecoAdms\DTOs\CommandResult;
use Athwari\LaravelZktecoAdms\DTOs\UserRecord;
use Athwari\LaravelZktecoAdms\Enums\AttendanceStatus;
use Athwari\LaravelZktecoAdms\Enums\CommandStatus;
use Athwari\LaravelZktecoAdms\Enums\CommandType;
use Athwari\LaravelZktecoAdms\Enums\DeviceEventType;
use Athwari\LaravelZktecoAdms\Enums\DeviceStatus;
use Athwari\LaravelZktecoAdms\Enums\UserPrivilege;
use Athwari\LaravelZktecoAdms\Enums\VerifyMode;
use Athwari\LaravelZktecoAdms\Events\DeviceConnected;
use Athwari\LaravelZktecoAdms\Events\UserQueryReceived;
use Athwari\LaravelZktecoAdms\Models\ZktecoAttendanceLog;
use Athwari\LaravelZktecoAdms\Models\ZktecoDevice;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceCommand;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Athwari\LaravelZktecoAdms\Models\ZktecoUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

test('dto helpers expose array and enum metadata', function () {
    $attendance = new AttendanceRecord('1001', now(), 0, 1, 'WC1', 'SN001');
    $commandEntry = new CommandEntry(7, 'INFO');
    $commandResult = new CommandResult('SN001', 7, 0, 'INFO', 'INFO');
    $userRecord = new UserRecord('1001', 'Jane Doe', 14, 'CARD1', 'secret');

    expect($attendance->statusEnum())->toBe(AttendanceStatus::CheckIn)
        ->and($attendance->verifyModeEnum())->toBe(VerifyMode::Fingerprint)
        ->and($attendance->statusLabel())->toContain('attendance_status.check_in')
        ->and($attendance->verifyModeLabel())->toBe('Fingerprint')
        ->and($attendance->toArray()['serial_number'])->toBe('SN001')
        ->and($commandEntry->toWireFormat())->toBe("C:7:INFO\n")
        ->and($commandEntry->toArray())->toBe(['id' => 7, 'command' => 'INFO'])
        ->and($commandResult->isSuccess())->toBeTrue()
        ->and($commandResult->toArray()['queued_command'])->toBe('INFO')
        ->and($userRecord->isAdmin())->toBeTrue()
        ->and($userRecord->toArray()['card'])->toBe('CARD1');
});

test('enum helpers expose colors, icons, and unknown fallbacks', function () {
    expect(AttendanceStatus::CheckIn->getLabel())->toContain('attendance_status.check_in')
        ->and(AttendanceStatus::CheckIn->getColor())->toBe('success')
        ->and(AttendanceStatus::CheckOut->getIcon())->toBe('heroicon-o-arrow-left-on-rectangle')
        ->and(AttendanceStatus::BreakOut->getColor())->toBe('warning')
        ->and(AttendanceStatus::BreakIn->getColor())->toBe('info')
        ->and(AttendanceStatus::OvertimeIn->getColor())->toBe('primary')
        ->and(AttendanceStatus::OvertimeOut->getColor())->toBe('gray')
        ->and(AttendanceStatus::nameFor(999))->toBe('Unknown (999)')
        ->and(CommandStatus::Pending->getLabel())->toContain('command_status.pending')
        ->and(CommandStatus::Failed->getColor())->toBe('danger')
        ->and(CommandStatus::Pending->getIcon())->toBe('heroicon-m-clock')
        ->and(CommandStatus::Sent->getIcon())->toBe('heroicon-m-paper-airplane')
        ->and(CommandStatus::Acknowledged->getIcon())->toBe('heroicon-m-check-circle')
        ->and(CommandType::Info->getLabel())->toContain('command_type.INFO')
        ->and(CommandType::Info->getColor())->toBe('info')
        ->and(CommandType::Reboot->getColor())->toBe('warning')
        ->and(CommandType::Clear->getColor())->toBe('danger')
        ->and(CommandType::Data->getColor())->toBe('primary')
        ->and(CommandType::Check->getIcon())->toBe('heroicon-m-signal')
        ->and(DeviceEventType::Registered->getLabel())->toContain('device_event_type.registered')
        ->and(DeviceEventType::Connected->getColor())->toBe('success')
        ->and(DeviceEventType::Registered->getIcon())->toBe('heroicon-m-plus')
        ->and(DeviceEventType::Disconnected->getIcon())->toBe('heroicon-m-signal-slash')
        ->and(DeviceEventType::InfoReceived->getColor())->toBe('info')
        ->and(DeviceEventType::CommandSent->getColor())->toBe('warning')
        ->and(DeviceEventType::CommandAcknowledged->getIcon())->toBe('heroicon-m-check-circle')
        ->and(DeviceEventType::AttendanceSynced->getIcon())->toBe('heroicon-m-clipboard-document-check')
        ->and(DeviceEventType::UserSynced->getColor())->toBe('primary')
        ->and(DeviceEventType::StatusChanged->getColor())->toBe('gray')
        ->and(DeviceStatus::Online->getLabel())->toContain('device_status.online')
        ->and(DeviceStatus::Online->getColor())->toBe('success')
        ->and(DeviceStatus::Offline->getColor())->toBe('danger')
        ->and(DeviceStatus::Unknown->getIcon())->toBe('heroicon-m-question-mark-circle')
        ->and(UserPrivilege::User->getLabel())->toContain('user_privilege.user')
        ->and(UserPrivilege::User->getColor())->toBe('gray')
        ->and(UserPrivilege::Admin->getColor())->toBe('danger')
        ->and(UserPrivilege::nameFor(999))->toBe('Unknown (999)')
        ->and(VerifyMode::Password->getLabel())->toBe('Password')
        ->and(VerifyMode::Password->getIcon())->toBe('heroicon-m-key')
        ->and(VerifyMode::Card->getColor())->toBe('warning')
        ->and(VerifyMode::FingerprintCard->getLabel())->toBe('Fingerprint+Card')
        ->and(VerifyMode::FingerprintPassword->getLabel())->toBe('Fingerprint+Password')
        ->and(VerifyMode::CardPassword->getLabel())->toBe('Card+Password')
        ->and(VerifyMode::CardFingerprintPassword->getLabel())->toBe('Card+Fingerprint+Password')
        ->and(VerifyMode::Other->getLabel())->toBe('Other')
        ->and(VerifyMode::Face->getIcon())->toBe('heroicon-m-user')
        ->and(VerifyMode::Palm->getColor())->toBe('info')
        ->and(VerifyMode::nameFor(99))->toBe('Unknown (99)');
});

test('device command and event models apply lifecycle updates and relations', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'MODEL001',
        'status' => DeviceStatus::Unknown,
    ]);

    $command = ZktecoDeviceCommand::query()->create([
        'device_id' => $device->id,
        'command_id' => 10,
        'command_type' => CommandType::Info,
        'command_content' => 'INFO',
        'status' => CommandStatus::Pending,
        'retry_count' => 0,
    ]);

    $command->markAsSent();
    expect($command->fresh()->status)->toBe(CommandStatus::Sent);

    $command->markAsAcknowledged('OK', 0);
    expect($command->fresh()->isConfirmed())->toBeTrue();

    $command->markAsFailed('Failed');
    expect($command->fresh()->status)->toBe(CommandStatus::Failed);

    $command->retry();
    expect($command->fresh()->status)->toBe(CommandStatus::Pending)
        ->and($command->fresh()->retry_count)->toBe(1);

    $event = ZktecoDeviceEvent::record($device->id, DeviceEventType::Connected, ['reason' => 'heartbeat'], '127.0.0.1');

    expect($event->device->is($device))->toBeTrue()
        ->and($event->payload)->toBe(['reason' => 'heartbeat'])
        ->and($event->ip_address)->toBe('127.0.0.1');
});

test('device and user models expose their relationships and helper methods', function () {
    config()->set('zkteco-adms.user_model', TestHostUser::class);

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    $appUserId = \Illuminate\Support\Facades\DB::table('users')->insertGetId(['name' => 'Host User', 'created_at' => now(), 'updated_at' => now()]);

    $device = ZktecoDevice::query()->create([
        'serial_number' => 'REL001',
        'timezone' => null,
        'last_activity_at' => now(),
    ]);

    $zktecoUser = ZktecoUser::query()->create([
        'pin' => 'PIN001',
        'device_id' => $device->id,
        'app_user_id' => $appUserId,
        'privilege' => UserPrivilege::User,
        'is_enabled' => true,
    ]);

    ZktecoAttendanceLog::query()->create([
        'device_id' => $device->id,
        'pin' => 'PIN001',
        'recorded_at' => now(),
        'status' => AttendanceStatus::CheckIn,
        'verify_mode' => VerifyMode::Fingerprint,
        'work_code' => '',
    ]);

    $attendanceLog = ZktecoAttendanceLog::query()->firstOrFail();

    expect($device->attendanceLogs()->count())->toBe(1)
        ->and($device->zktecoUsers()->count())->toBe(1)
        ->and($device->getEffectiveTimezone())->toBe('UTC')
        ->and($device->isOnline())->toBeTrue()
        ->and($zktecoUser->attendanceLogs()->count())->toBe(1)
        ->and($attendanceLog->getTable())->toBe('zkteco_attendance_logs')
        ->and($attendanceLog->device->is($device))->toBeTrue()
        ->and($attendanceLog->zktecoUser->is($zktecoUser))->toBeTrue()
        ->and($zktecoUser->appUser)->not->toBeNull()
        ->and($zktecoUser->appUser->getKey())->toBe($appUserId);
});

test('simple events keep their constructor payloads', function () {
    $device = ZktecoDevice::query()->create(['serial_number' => 'EVENT001']);
    $userRecord = new UserRecord('1001', 'Jane Doe', 0, '', '');

    $connected = new DeviceConnected($device);
    $queryReceived = new UserQueryReceived('EVENT001', [$userRecord]);

    expect($connected->device->is($device))->toBeTrue()
        ->and($queryReceived->serialNumber)->toBe('EVENT001')
        ->and($queryReceived->users)->toHaveCount(1)
        ->and($queryReceived->users[0])->toBe($userRecord);
});

class TestHostUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
