<?php

namespace Athwari\LaravelZktecoAdms\Services;

use Athwari\LaravelZktecoAdms\DTOs\AttendanceRecord;
use Athwari\LaravelZktecoAdms\DTOs\CommandResult;
use Athwari\LaravelZktecoAdms\DTOs\UserRecord;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Parser for ZKTeco ADMS protocol data formats.
 *
 * Handles parsing of:
 * - ATTLOG attendance records (tab-separated, multi-format timestamps)
 * - Key=value pairs (device info and registry payloads)
 * - USERINFO records (tab-separated key=value fields)
 * - OPERLOG operation records (user/fingerprint/face data)
 * - Command result confirmations (batched and multiline formats)
 * - Serial number validation
 */
class AttendanceParser
{
    /** Maximum allowed serial number length. */
    private const MAX_SERIAL_NUMBER_LENGTH = 64;

    /** Regex pattern for valid serial numbers. */
    private const SERIAL_NUMBER_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    /** ZKTeco timestamp format. */
    private const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /** Maximum body preview length for logging. */
    private const MAX_BODY_PREVIEW_LEN = 200;

    /** ATTLOG tab-separated field indices. */
    private const ATT_FIELD_PIN = 0;

    private const ATT_FIELD_TIMESTAMP = 1;

    private const ATT_FIELD_STATUS = 2;

    private const ATT_FIELD_VERIFY_MODE = 3;

    private const ATT_FIELD_WORK_CODE = 4;

    private const ATT_MIN_FIELDS = 2;

    /**
     * Validate that a serial number matches the expected format.
     */
    public function validateSerialNumber(string $sn): bool
    {
        if ($sn === '' || strlen($sn) > self::MAX_SERIAL_NUMBER_LENGTH) {
            return false;
        }

        return (bool) preg_match(self::SERIAL_NUMBER_PATTERN, $sn);
    }

    /**
     * Parse attendance records from device ATTLOG data.
     *
     * @param  string  $data  Raw ATTLOG body from the device
     * @param  string  $serialNumber  Device serial number
     * @param  string  $timezone  Timezone name for interpreting device-local timestamps
     * @return AttendanceRecord[]
     */
    public function parseAttendanceRecords(string $data, string $serialNumber, string $timezone = 'UTC'): array
    {
        $records = [];
        $skipped = 0;

        try {
            $tz = new DateTimeZone($timezone);
        } catch (Exception) {
            $tz = new DateTimeZone('UTC');
            Log::warning('Invalid timezone, falling back to UTC', [
                'timezone' => $timezone,
                'device' => $serialNumber,
            ]);
        }

        $lines = explode("\n", trim($data, "\n\r"));

        foreach ($lines as $line) {
            $line = rtrim($line, "\r");
            if (trim($line) === '') {
                continue;
            }

            $parts = explode("\t", $line);

            if (count($parts) < self::ATT_MIN_FIELDS) {
                $skipped++;
                Log::warning('Skipping malformed ATTLOG line', [
                    'device' => $serialNumber,
                    'fields' => count($parts),
                    'line' => $line,
                ]);

                continue;
            }

            $pin = trim($parts[self::ATT_FIELD_PIN]);
            if ($pin === '') {
                $skipped++;
                Log::warning('Skipping ATTLOG line with empty PIN', [
                    'device' => $serialNumber,
                    'line' => $line,
                ]);

                continue;
            }

            $timestamp = $this->parseTimestamp($parts[self::ATT_FIELD_TIMESTAMP], $tz);
            if (! $timestamp instanceof Carbon) {
                $skipped++;
                Log::warning('Skipping ATTLOG line with unparseable timestamp', [
                    'device' => $serialNumber,
                    'timestamp' => $parts[self::ATT_FIELD_TIMESTAMP],
                    'line' => $line,
                ]);

                continue;
            }

            $status = 0;
            if (isset($parts[self::ATT_FIELD_STATUS])) {
                $status = filter_var($parts[self::ATT_FIELD_STATUS], FILTER_VALIDATE_INT);
                if ($status === false) {
                    Log::warning('Non-integer Status field, defaulting to 0', [
                        'device' => $serialNumber,
                        'value' => $parts[self::ATT_FIELD_STATUS],
                    ]);
                    $status = 0;
                }
            }

            $verifyMode = 0;
            if (isset($parts[self::ATT_FIELD_VERIFY_MODE])) {
                $verifyMode = filter_var($parts[self::ATT_FIELD_VERIFY_MODE], FILTER_VALIDATE_INT);
                if ($verifyMode === false) {
                    Log::warning('Non-integer VerifyMode field, defaulting to 0', [
                        'device' => $serialNumber,
                        'value' => $parts[self::ATT_FIELD_VERIFY_MODE],
                    ]);
                    $verifyMode = 0;
                }
            }

            $workCode = $parts[self::ATT_FIELD_WORK_CODE] ?? '';

            $records[] = new AttendanceRecord(
                pin: $pin,
                timestamp: $timestamp,
                status: $status,
                verifyMode: $verifyMode,
                workCode: $workCode,
                serialNumber: $serialNumber,
            );
        }

        if ($skipped > 0) {
            Log::warning('Skipped malformed ATTLOG lines', [
                'device' => $serialNumber,
                'skipped' => $skipped,
                'total' => count($records) + $skipped,
            ]);
        }

        return $records;
    }

    /**
     * Parse key=value pairs separated by a given separator.
     *
     * @param  string  $data  Raw data string
     * @param  string  $separator  Separator between key=value pairs
     * @param  callable|null  $keyTransform  Optional transformation applied to each key
     * @return array<string, string>
     */
    public function parseKVPairs(string $data, string $separator = "\n", ?callable $keyTransform = null): array
    {
        $info = [];
        $parts = explode($separator, trim($data));

        foreach ($parts as $part) {
            $part = trim($part);
            $eqPos = strpos($part, '=');
            if ($eqPos !== false) {
                $key = trim(substr($part, 0, $eqPos));
                $value = trim(substr($part, $eqPos + 1));

                if ($keyTransform !== null) {
                    $key = $keyTransform($key);
                }

                $info[$key] = $value;
            }
        }

        return $info;
    }

    /**
     * Parse user records from USERINFO data pushed by the device.
     *
     * @param  string  $data  Raw USERINFO body from the device
     * @param  string  $serialNumber  Device serial number
     * @return UserRecord[]
     */
    public function parseUserRecords(string $data, string $serialNumber): array
    {
        $records = [];
        $skipped = 0;

        $lines = explode("\n", trim($data));

        foreach ($lines as $line) {
            $line = rtrim($line, "\r");
            if ($line === '') {
                continue;
            }

            $fields = [];
            $parts = explode("\t", $line);
            foreach ($parts as $part) {
                $eqPos = strpos($part, '=');
                if ($eqPos !== false) {
                    $key = trim(substr($part, 0, $eqPos));
                    $value = trim(substr($part, $eqPos + 1));
                    $fields[$key] = $value;
                }
            }

            $pin = $fields['PIN'] ?? '';
            if ($pin === '') {
                $skipped++;
                Log::warning('Skipping USERINFO line without PIN', [
                    'device' => $serialNumber,
                    'line_len' => strlen($line),
                ]);

                continue;
            }

            $privilege = filter_var($fields['Privilege'] ?? '0', FILTER_VALIDATE_INT);
            if ($privilege === false) {
                $privilege = 0;
            }

            $records[] = new UserRecord(
                pin: $pin,
                name: $fields['Name'] ?? '',
                privilege: $privilege,
                card: $fields['Card'] ?? '',
                password: $fields['Password'] ?? '',
            );
        }

        if ($skipped > 0) {
            Log::warning('Skipped malformed USERINFO lines', [
                'device' => $serialNumber,
                'skipped' => $skipped,
                'total' => count($records) + $skipped,
            ]);
        }

        return $records;
    }

    /**
     * Parse operation log records (user/fingerprint/face data).
     *
     * @param  string  $body  Raw OPERLOG body
     * @return array<int, array<string, mixed>>
     */
    public function parseOperationLogs(string $body): array
    {
        $operations = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($body));

        foreach ($lines as $line) {
            if (in_array(trim($line), ['', '0'], true)) {
                continue;
            }

            if (str_starts_with($line, 'USER')) {
                $operations[] = $this->parseOperLogLine($line, 'user');
            } elseif (str_starts_with($line, 'FP')) {
                $operations[] = $this->parseOperLogLine($line, 'fingerprint');
            } elseif (str_starts_with($line, 'FACE')) {
                $operations[] = $this->parseOperLogLine($line, 'face');
            }
        }

        return $operations;
    }

    /**
     * Parse command result confirmations from a devicecmd body.
     *
     * @param  string  $body  Raw devicecmd body
     * @param  string  $serialNumber  Device serial number
     * @return CommandResult[]
     */
    public function parseCommandResults(string $body, string $serialNumber): array
    {
        $results = [];
        $currentId = null;
        $currentReturnCode = 0;
        $currentCommand = '';
        $hasId = false;

        $body = str_replace("\n", '&', $body);
        $parts = explode('&', $body);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $eqPos = strpos($part, '=');
            if ($eqPos === false) {
                continue;
            }

            $key = strtoupper(trim(substr($part, 0, $eqPos)));
            $value = trim(substr($part, $eqPos + 1));

            switch ($key) {
                case 'ID':
                    $id = filter_var($value, FILTER_VALIDATE_INT);
                    if ($id === false) {
                        Log::warning('devicecmd: unparseable ID', [
                            'device' => $serialNumber,
                            'value' => $value,
                        ]);

                        continue 2;
                    }

                    if ($hasId) {
                        $results[] = new CommandResult(
                            serialNumber: $serialNumber,
                            id: $currentId,
                            returnCode: $currentReturnCode,
                            command: $currentCommand,
                        );
                    }

                    $currentId = $id;
                    $currentReturnCode = 0;
                    $currentCommand = '';
                    $hasId = true;

                    break;

                case 'RETURN':
                    $code = filter_var($value, FILTER_VALIDATE_INT);
                    if ($code !== false) {
                        $currentReturnCode = $code;
                    }

                    break;

                case 'CMD':
                    $currentCommand = $value;

                    break;
            }
        }

        if ($hasId) {
            $results[] = new CommandResult(
                serialNumber: $serialNumber,
                id: $currentId,
                returnCode: $currentReturnCode,
                command: $currentCommand,
            );
        }

        return $results;
    }

    /**
     * Remove a leading "~" prefix from a string.
     */
    public static function trimTildePrefix(string $s): string
    {
        return ltrim($s, '~');
    }

    /**
     * Return a truncated preview of body data for logging.
     */
    public static function bodyPreview(string $body): string
    {
        if (strlen($body) > self::MAX_BODY_PREVIEW_LEN) {
            return substr($body, 0, self::MAX_BODY_PREVIEW_LEN).'...';
        }

        return $body;
    }

    /**
     * Parse an OPERLOG line extracting key=value pairs.
     *
     * @return array<string, mixed>
     */
    private function parseOperLogLine(string $line, string $type): array
    {
        preg_match_all('/(\w+)=([^\t]*)/', $line, $matches, PREG_SET_ORDER);

        $data = ['type' => $type];

        foreach ($matches as $match) {
            $key = strtolower($match[1]);
            $data[$key] = $match[2];
        }

        return $data;
    }

    /**
     * Parse a timestamp string, trying "Y-m-d H:i:s" format first, then Unix epoch.
     */
    private function parseTimestamp(string $value, DateTimeZone $tz): ?Carbon
    {
        $value = trim($value);

        try {
            return Carbon::createFromFormat(self::TIMESTAMP_FORMAT, $value, $tz);
        } catch (Exception) {
            // Fall through to epoch check
        }

        if (ctype_digit($value) || (str_starts_with($value, '-') && ctype_digit(substr($value, 1)))) {
            $epoch = (int) $value;

            return Carbon::createFromTimestamp($epoch);
        }

        return null;
    }
}
