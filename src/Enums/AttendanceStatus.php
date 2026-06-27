<?php

namespace Athwari\LaravelZktecoAdms\Enums;

/**
 * Attendance status values reported by ZKTeco devices.
 *
 * These values represent the punch state when a user interacts
 * with the biometric device via the ADMS (Push) HTTP protocol.
 */
enum AttendanceStatus: int
{
    case CheckIn = 0;

    case CheckOut = 1;

    case BreakOut = 2;

    case BreakIn = 3;

    case OvertimeIn = 4;

    case OvertimeOut = 5;

    public function getLabel(): string
    {
        return __('filament-zkteco-adms::default.enums.attendance_status.'.$this->labelKey());
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CheckIn => 'success',
            self::CheckOut => 'danger',
            self::BreakOut => 'warning',
            self::BreakIn => 'info',
            self::OvertimeIn => 'primary',
            self::OvertimeOut => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CheckIn => 'heroicon-o-arrow-right-on-rectangle',
            self::CheckOut => 'heroicon-o-arrow-left-on-rectangle',
            self::BreakOut, self::BreakIn => 'heroicon-o-clock',
            self::OvertimeIn, self::OvertimeOut => 'heroicon-o-fire',
        };
    }

    /**
     * Get a human-readable name for any status value, including unknown ones.
     */
    public static function nameFor(int $value): string
    {
        $instance = self::tryFrom($value);

        return $instance ? $instance->getLabel() : "Unknown ({$value})";
    }

    private function labelKey(): string
    {
        return match ($this) {
            self::CheckIn => 'check_in',
            self::CheckOut => 'check_out',
            self::BreakOut => 'break_out',
            self::BreakIn => 'break_in',
            self::OvertimeIn => 'overtime_in',
            self::OvertimeOut => 'overtime_out',
        };
    }
}
