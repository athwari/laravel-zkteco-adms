<?php

namespace Athwari\LaravelZktecoAdms\Http\Controllers;

use Athwari\LaravelZktecoAdms\DTOs\CommandResult;
use Athwari\LaravelZktecoAdms\Enums\DeviceEventType;
use Athwari\LaravelZktecoAdms\Events\AttendanceReceived;
use Athwari\LaravelZktecoAdms\Events\CommandResultReceived;
use Athwari\LaravelZktecoAdms\Events\DeviceInfoReceived;
use Athwari\LaravelZktecoAdms\Events\DeviceRegistered;
use Athwari\LaravelZktecoAdms\Events\UserQueryReceived;
use Athwari\LaravelZktecoAdms\Events\UserSynced;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceLimitReachedException;
use Athwari\LaravelZktecoAdms\Exceptions\DeviceNotFoundException;
use Athwari\LaravelZktecoAdms\Exceptions\InvalidSerialNumberException;
use Athwari\LaravelZktecoAdms\Models\ZktecoAttendanceLog;
use Athwari\LaravelZktecoAdms\Models\ZktecoDeviceEvent;
use Athwari\LaravelZktecoAdms\Models\ZktecoUser;
use Athwari\LaravelZktecoAdms\Services\AttendanceParser;
use Athwari\LaravelZktecoAdms\Services\CommandManager;
use Athwari\LaravelZktecoAdms\Services\DeviceManager;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Controller handling all ADMS protocol endpoints.
 *
 * Endpoints:
 *   GET/POST /iclock/cdata       - Attendance logs, device info, user query results
 *   GET/POST /iclock/registry    - Device registration & capabilities
 *   GET      /iclock/getrequest  - Device polling for pending commands
 *   POST     /iclock/devicecmd   - Command execution confirmations
 *   GET      /iclock/inspect     - JSON device snapshot (opt-in)
 */
class AdmsController extends Controller
{
    public function __construct(
        private readonly DeviceManager $deviceManager,
        private readonly CommandManager $commandManager,
        private readonly AttendanceParser $parser,
    ) {}

    /**
     * Handle /iclock/cdata endpoint.
     */
    public function handleCdata(Request $request): Response
    {
        $serialNumber = $this->requireDevice($request);
        if ($serialNumber instanceof Response) {
            return $serialNumber;
        }

        $table = $request->query('table', '');

        Log::debug('cdata request', [
            'method' => $request->method(),
            'device' => $serialNumber,
            'table' => $table,
        ]);

        return match ($table) {
            'ATTLOG' => $this->handleAttLog($request, $serialNumber),
            'OPERLOG' => $this->handleOperLog($request, $serialNumber),
            'USERINFO' => $this->handleUserInfo($request, $serialNumber),
            default => $this->handleInfoOrCommands($request, $serialNumber),
        };
    }

    /**
     * Handle /iclock/getrequest endpoint.
     */
    public function handleGetRequest(Request $request): Response
    {
        $serialNumber = $this->requireDevice($request);
        if ($serialNumber instanceof Response) {
            return $serialNumber;
        }

        Log::debug('getrequest', ['device' => $serialNumber]);

        return $this->writeCommandsOrOK($serialNumber);
    }

    /**
     * Handle /iclock/devicecmd endpoint.
     */
    public function handleDeviceCmd(Request $request): Response
    {
        $serialNumber = $this->requireDevice($request);
        if ($serialNumber instanceof Response) {
            return $serialNumber;
        }

        Log::debug('devicecmd', ['device' => $serialNumber]);

        $body = $request->getContent();

        if ((string) $body !== '') {
            Log::debug('devicecmd body', [
                'device' => $serialNumber,
                'preview' => AttendanceParser::bodyPreview($body),
            ]);
        }

        $results = $this->parser->parseCommandResults($body, $serialNumber);

        foreach ($results as $result) {
            $queuedCommand = $this->commandManager->getQueuedCommand($result->id);
            $enrichedResult = new CommandResult(
                serialNumber: $result->serialNumber,
                id: $result->id,
                returnCode: $result->returnCode,
                command: $result->command,
                queuedCommand: $queuedCommand,
            );

            Log::info('Command result', [
                'device' => $serialNumber,
                'id' => $enrichedResult->id,
                'return' => $enrichedResult->returnCode,
                'cmd' => $enrichedResult->command,
            ]);

            $this->commandManager->confirmCommand($enrichedResult->id, $enrichedResult->returnCode);

            if (config('zkteco-adms.events.dispatch_command_result', true)) {
                event(new CommandResultReceived($enrichedResult));
            }
        }

        return response('OK', 200);
    }

    /**
     * Handle /iclock/registry endpoint.
     */
    public function handleRegistry(Request $request): Response
    {
        $serialNumber = $this->requireDevice($request);
        if ($serialNumber instanceof Response) {
            return $serialNumber;
        }

        Log::debug('registry request', [
            'method' => $request->method(),
            'device' => $serialNumber,
        ]);

        $body = $request->getContent();

        if ((string) $body !== '') {
            Log::debug('registry body', ['preview' => AttendanceParser::bodyPreview($body)]);

            $info = $this->parser->parseKVPairs($body, ',', AttendanceParser::trimTildePrefix(...));
            $this->deviceManager->updateDeviceOptions($serialNumber, $info);

            if (config('zkteco-adms.events.dispatch_device_registered', true)) {
                event(new DeviceRegistered($serialNumber, $info));
            }
        }

        return response('OK', 200);
    }

    /**
     * Handle /iclock/inspect endpoint.
     */
    public function handleInspect(Request $request): Response
    {
        if (! config('zkteco-adms.enable_inspect', false)) {
            return response('Not Found', 404);
        }

        $snapshots = $this->deviceManager->getDeviceSnapshots();

        $payload = [
            'devices' => array_map(fn ($s) => $s->toArray(), $snapshots),
            'count' => count($snapshots),
            'time' => now()->toIso8601String(),
        ];

        return response(json_encode($payload), 200)
            ->header('Content-Type', 'application/json');
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    /**
     * Validate SN, register device, and update activity.
     */
    private function requireDevice(Request $request): string|Response
    {
        $sn = (string) $request->query('SN', '');

        if ($sn === '') {
            return response('Missing SN parameter', 400);
        }

        if (! $this->parser->validateSerialNumber($sn)) {
            Log::warning('Invalid serial number', ['sn' => $sn]);

            return response('Invalid SN parameter', 400);
        }

        $attributes = [
            'device_type' => $request->query('DeviceType'),
            'language' => $request->query('language'),
            'push_version' => $request->query('pushver'),
            'firmware_version' => $request->query('FirmwareVersion'),
        ];

        try {
            $this->deviceManager->registerDevice($sn, $request->ip(), $attributes);
        } catch (DeviceLimitReachedException) {
            Log::warning('Device limit reached', [
                'device' => $sn,
                'limit' => config('zkteco-adms.device.max_devices'),
            ]);

            return response('Device limit reached', 503);
        } catch (InvalidSerialNumberException) {
            return response('Invalid SN parameter', 400);
        } catch (DeviceNotFoundException) {
            return response('Device not registered', 403);
        }

        $this->deviceManager->updateActivity($sn, $request->ip(), $attributes);

        return $sn;
    }

    /**
     * Handle ATTLOG table — parse and store attendance records.
     */
    private function handleAttLog(Request $request, string $serialNumber): Response
    {
        $body = $request->getContent();

        if ((string) $body !== '') {
            Log::debug('ATTLOG body', ['preview' => AttendanceParser::bodyPreview($body)]);
        }

        $timezone = $this->deviceManager->getDeviceTimezone($serialNumber);
        $records = $this->parser->parseAttendanceRecords($body, $serialNumber, $timezone);

        $device = $this->deviceManager->getDevice($serialNumber);

        $attendanceModel = config('zkteco-adms.models.attendance_log', ZktecoAttendanceLog::class);
        $storageTimezone = $this->storageTimezone($serialNumber);

        foreach ($records as $record) {
            $attendanceModel::create([
                'device_id' => $device?->id,
                'pin' => $record->pin,
                'recorded_at' => $record->timestamp,
                'occurred_at' => DateTimeImmutable::createFromInterface($record->timestamp)
                    ->setTimezone($storageTimezone),
                'status' => $record->status,
                'verify_mode' => $record->verifyMode,
                'work_code' => $record->workCode,
            ]);
        }

        if (count($records) > 0) {
            if (config('zkteco-adms.events.dispatch_device_event', true) && $device) {
                ZktecoDeviceEvent::record(
                    $device->id,
                    DeviceEventType::AttendanceSynced,
                    ['count' => count($records)]
                );
            }

            if (config('zkteco-adms.events.dispatch_attendance_received', true)) {
                event(new AttendanceReceived($serialNumber, $records));
            }
        }

        return response('OK: '.count($records), 200);
    }

    /**
     * Resolve the timezone used to normalize persisted attendance timestamps.
     */
    private function storageTimezone(string $serialNumber): DateTimeZone
    {
        $timezone = config('zkteco-adms.storage_timezone', 'UTC');
        $timezone = is_string($timezone) ? $timezone : 'UTC';

        try {
            return new DateTimeZone($timezone);
        } catch (Exception) {
            Log::warning('Invalid storage timezone, falling back to UTC', [
                'timezone' => $timezone,
                'device' => $serialNumber,
            ]);

            return new DateTimeZone('UTC');
        }
    }

    /**
     * Handle OPERLOG table — parse operation logs and sync users.
     */
    private function handleOperLog(Request $request, string $serialNumber): Response
    {
        $body = $request->getContent();

        if ((string) $body !== '') {
            $operations = $this->parser->parseOperationLogs($body);
            $device = $this->deviceManager->getDevice($serialNumber);

            $userModel = config('zkteco-adms.models.user', ZktecoUser::class);

            foreach ($operations as $op) {
                if (($op['type'] ?? '') === 'user' && isset($op['pin'])) {
                    $zktecoUser = $userModel::updateOrCreate(
                        ['pin' => $op['pin']],
                        [
                            'name' => $op['name'] ?? null,
                            'card_number' => $op['card'] ?? null,
                            'privilege' => (int) ($op['pri'] ?? 0),
                        ]
                    );

                    if (config('zkteco-adms.events.dispatch_user_synced', true) && $device) {
                        event(new UserSynced($zktecoUser, $device));
                    }
                }
            }

            if ($device && config('zkteco-adms.events.dispatch_device_event', true)) {
                ZktecoDeviceEvent::record(
                    $device->id,
                    DeviceEventType::UserSynced,
                    ['operation_count' => count($operations)]
                );
            }
        }

        return response('OK', 200);
    }

    /**
     * Handle USERINFO table — parse user records from device query response.
     */
    private function handleUserInfo(Request $request, string $serialNumber): Response
    {
        $body = $request->getContent();

        if ((string) $body !== '') {
            $users = $this->parser->parseUserRecords($body, $serialNumber);

            Log::debug('USERINFO records processed', [
                'count' => count($users),
                'device' => $serialNumber,
            ]);

            if (count($users) > 0) {
                event(new UserQueryReceived($serialNumber, $users));
            }
        }

        return response('OK', 200);
    }

    /**
     * Handle default case: device info POST or pending commands GET.
     */
    private function handleInfoOrCommands(Request $request, string $serialNumber): Response
    {
        if ($request->isMethod('POST')) {
            $body = $request->getContent();

            if ((string) $body !== '') {
                $info = $this->parser->parseKVPairs($body, "\n");

                $this->deviceManager->updateDeviceInfo($serialNumber, $info);

                if (config('zkteco-adms.events.dispatch_device_connected', true)) {
                    event(new DeviceInfoReceived($serialNumber, $info));
                }

                Log::debug('INFO body', ['preview' => AttendanceParser::bodyPreview($body)]);
            }
        }

        return $this->writeCommandsOrOK($serialNumber);
    }

    /**
     * Drain pending commands and write them in wire format, or "OK" if none.
     */
    private function writeCommandsOrOK(string $serialNumber): Response
    {
        $commands = $this->commandManager->drainCommands($serialNumber);

        if (count($commands) > 0) {
            $output = '';
            foreach ($commands as $entry) {
                $output .= $entry->toWireFormat();
            }

            return response($output, 200);
        }

        return response('OK', 200);
    }
}
