<?php

use Athwari\LaravelZktecoAdms\Services\AttendanceParser;

function attendanceParser(): AttendanceParser
{
    return new AttendanceParser();
}

// ---------------------------------------------------------------
// Serial Number Validation
// ---------------------------------------------------------------

test('valid serial numbers', function () {
    expect(attendanceParser()->validateSerialNumber('ABC123'))->toBeTrue()
        ->and(attendanceParser()->validateSerialNumber('DEVICE-001'))->toBeTrue()
        ->and(attendanceParser()->validateSerialNumber('device_test_123'))->toBeTrue()
        ->and(attendanceParser()->validateSerialNumber('A'))->toBeTrue();
});

test('invalid serial numbers', function () {
    expect(attendanceParser()->validateSerialNumber(''))->toBeFalse()
        ->and(attendanceParser()->validateSerialNumber('has space'))->toBeFalse()
        ->and(attendanceParser()->validateSerialNumber('has.dot'))->toBeFalse()
        ->and(attendanceParser()->validateSerialNumber(str_repeat('A', 65)))->toBeFalse();
});

// ---------------------------------------------------------------
// ATTLOG Parsing
// ---------------------------------------------------------------

test('parse single attendance record', function () {
    $data = "1001\t2024-03-15 08:30:00\t0\t1\t";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(1)
        ->and($records[0]->pin)->toBe('1001')
        ->and($records[0]->timestamp->format('Y-m-d H:i:s'))->toBe('2024-03-15 08:30:00')
        ->and($records[0]->status)->toBe(0)
        ->and($records[0]->verifyMode)->toBe(1)
        ->and($records[0]->serialNumber)->toBe('TEST001');
});

test('parse multiple attendance records', function () {
    $data = "1001\t2024-03-15 08:30:00\t0\t1\t\n1002\t2024-03-15 08:31:00\t1\t4\tWC01";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(2)
        ->and($records[1]->pin)->toBe('1002')
        ->and($records[1]->status)->toBe(1)
        ->and($records[1]->verifyMode)->toBe(4)
        ->and($records[1]->workCode)->toBe('WC01');
});

test('parse unix epoch timestamp', function () {
    $epoch = '1710488400'; // 2024-03-15 08:00:00 UTC
    $data = "1001\t{$epoch}\t0\t1\t";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(1)
        ->and($records[0]->timestamp->getTimestamp())->toBe(1710488400);
});

test('skip malformed lines', function () {
    $data = "too_few_fields\n1001\t2024-03-15 08:30:00\t0\t1\t";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(1)
        ->and($records[0]->pin)->toBe('1001');
});

test('skip empty pin', function () {
    $data = "\t2024-03-15 08:30:00\t0\t1\t";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(0);
});

test('skip unparseable timestamp', function () {
    $data = "1001\tnot-a-date\t0\t1\t";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(0);
});

test('handles crlf line endings', function () {
    $data = "1001\t2024-03-15 08:30:00\t0\t1\t\r\n1002\t2024-03-15 08:31:00\t1\t1\t";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(2);
});

test('minimal fields', function () {
    $data = "1001\t2024-03-15 08:30:00";
    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001');

    expect($records)->toHaveCount(1)
        ->and($records[0]->status)->toBe(0)
        ->and($records[0]->verifyMode)->toBe(0)
        ->and($records[0]->workCode)->toBe('');
});

test('invalid timezone falls back to utc and non integer fields default to zero', function () {
    $data = "1001\t2024-03-15 08:30:00\tbad\tbad\t";

    $records = attendanceParser()->parseAttendanceRecords($data, 'TEST001', 'Mars/Phobos');

    expect($records)->toHaveCount(1)
        ->and($records[0]->status)->toBe(0)
        ->and($records[0]->verifyMode)->toBe(0)
        ->and($records[0]->timestamp->getTimezone()->getName())->toBe('UTC');
});

// ---------------------------------------------------------------
// KV Pair Parsing
// ---------------------------------------------------------------

test('parse kv pairs newline separated', function () {
    $data = "FWVersion=Ver 8.1.1\nDeviceName=TestDevice\nIPAddress=192.168.1.100";
    $result = attendanceParser()->parseKVPairs($data, "\n");

    expect($result['FWVersion'])->toBe('Ver 8.1.1')
        ->and($result['DeviceName'])->toBe('TestDevice')
        ->and($result['IPAddress'])->toBe('192.168.1.100');
});

test('parse kv pairs with tilde transform', function () {
    $data = '~DeviceName=Test,~FWVersion=1.0,~MACAddress=AA:BB:CC';
    $result = attendanceParser()->parseKVPairs($data, ',', AttendanceParser::trimTildePrefix(...));

    expect($result['DeviceName'])->toBe('Test')
        ->and($result['FWVersion'])->toBe('1.0');
});

// ---------------------------------------------------------------
// User Record Parsing
// ---------------------------------------------------------------

test('parse user records', function () {
    $data = "PIN=1\tName=John Doe\tPrivilege=0\tCard=12345\tPassword=pass";
    $records = attendanceParser()->parseUserRecords($data, 'TEST001');

    expect($records)->toHaveCount(1)
        ->and($records[0]->pin)->toBe('1')
        ->and($records[0]->name)->toBe('John Doe')
        ->and($records[0]->privilege)->toBe(0)
        ->and($records[0]->card)->toBe('12345')
        ->and($records[0]->password)->toBe('pass');
});

test('skip user record without pin', function () {
    $data = "Name=John Doe\tPrivilege=0";
    $records = attendanceParser()->parseUserRecords($data, 'TEST001');

    expect($records)->toHaveCount(0);
});

test('parse user records defaults invalid privilege to zero', function () {
    $data = "PIN=1\tName=John Doe\tPrivilege=bad\tCard=12345";
    $records = attendanceParser()->parseUserRecords($data, 'TEST001');

    expect($records)->toHaveCount(1)
        ->and($records[0]->privilege)->toBe(0);
});

// ---------------------------------------------------------------
// OPERLOG Parsing
// ---------------------------------------------------------------

test('parse operation logs with user records', function () {
    $data = "USER PIN=1\tName=John\tPri=0\tCard=12345";
    $operations = attendanceParser()->parseOperationLogs($data);

    expect($operations)->toHaveCount(1)
        ->and($operations[0]['type'])->toBe('user')
        ->and($operations[0]['pin'])->toBe('1');
});

test('parse operation logs with fingerprint records', function () {
    $data = "FP PIN=1\tFID=0\tSize=1024\tTMP=base64data";
    $operations = attendanceParser()->parseOperationLogs($data);

    expect($operations)->toHaveCount(1)
        ->and($operations[0]['type'])->toBe('fingerprint');
});

test('parse operation logs with face records and zero lines', function () {
    $data = "0\nFACE PIN=1\tName=John";
    $operations = attendanceParser()->parseOperationLogs($data);

    expect($operations)->toHaveCount(1)
        ->and($operations[0]['type'])->toBe('face')
        ->and($operations[0]['pin'])->toBe('1');
});

test('parse operation logs ignores empty lines', function () {
    $data = "\n\n";
    $operations = attendanceParser()->parseOperationLogs($data);

    expect($operations)->toHaveCount(0);
});

// ---------------------------------------------------------------
// Command Result Parsing
// ---------------------------------------------------------------

test('parse single command result', function () {
    $body = 'ID=1&Return=0&CMD=INFO';
    $results = attendanceParser()->parseCommandResults($body, 'TEST001');

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe(1)
        ->and($results[0]->returnCode)->toBe(0)
        ->and($results[0]->command)->toBe('INFO')
        ->and($results[0]->isSuccess())->toBeTrue();
});

test('parse batched command results', function () {
    $body = "ID=1&Return=0&CMD=DATA\nID=2&Return=0&CMD=DATA";
    $results = attendanceParser()->parseCommandResults($body, 'TEST001');

    expect($results)->toHaveCount(2)
        ->and($results[0]->id)->toBe(1)
        ->and($results[1]->id)->toBe(2);
});

test('parse failed command result', function () {
    $body = 'ID=5&Return=-1&CMD=CHECK';
    $results = attendanceParser()->parseCommandResults($body, 'TEST001');

    expect($results)->toHaveCount(1)
        ->and($results[0]->returnCode)->toBe(-1)
        ->and($results[0]->isSuccess())->toBeFalse();
});

test('parse shell multiline format', function () {
    $body = "ID=32\nReturn=0\nCMD=Shell\nContent=some output";
    $results = attendanceParser()->parseCommandResults($body, 'TEST001');

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe(32)
        ->and($results[0]->command)->toBe('Shell');
});

test('parse command results skips invalid ids and supports empty previews', function () {
    $body = 'ID=bad&Return=0&CMD=INFO&ID=9&Return=1&CMD=CHECK';
    $results = attendanceParser()->parseCommandResults($body, 'TEST001');

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe(9)
        ->and($results[0]->returnCode)->toBe(1)
        ->and(AttendanceParser::trimTildePrefix('~FWVersion'))->toBe('FWVersion')
        ->and(AttendanceParser::bodyPreview(str_repeat('A', 300)))->toEndWith('...')
        ->and(AttendanceParser::bodyPreview('short'))->toBe('short');
});
