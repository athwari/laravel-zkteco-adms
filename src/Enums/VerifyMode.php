<?php

namespace Athwari\LaravelZktecoAdms\Enums;

/**
 * Verification mode constants for the ADMS protocol.
 *
 * These values represent the verification method used by ZKTeco devices
 * when recording attendance. Devices may report different numeric codes
 * depending on firmware version and configured verification rules.
 */
enum VerifyMode: int
{
    case Password = 0;

    case Fingerprint = 1;

    case CardLegacy = 2;

    case PasswordAlt = 3;

    case Card = 4;

    case FingerprintCard = 5;

    case FingerprintPassword = 6;

    case CardPassword = 7;

    case CardFingerprintPassword = 8;

    case Other = 9;

    case Face = 15;

    case Palm = 25;

    public function getLabel(): string
    {
        return match ($this) {
            self::Password, self::PasswordAlt => 'Password',
            self::Fingerprint => 'Fingerprint',
            self::CardLegacy, self::Card => 'Card',
            self::FingerprintCard => 'Fingerprint+Card',
            self::FingerprintPassword => 'Fingerprint+Password',
            self::CardPassword => 'Card+Password',
            self::CardFingerprintPassword => 'Card+Fingerprint+Password',
            self::Other => 'Other',
            self::Face => 'Face',
            self::Palm => 'Palm',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fingerprint => 'success',
            self::Face => 'primary',
            self::Palm => 'info',
            self::Card, self::CardLegacy => 'warning',
            self::Password, self::PasswordAlt => 'gray',
            default => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Fingerprint => 'heroicon-m-fingerprint',
            self::Face => 'heroicon-m-user',
            self::Palm => 'heroicon-m-hand-raised',
            self::Card, self::CardLegacy => 'heroicon-m-credit-card',
            self::Password, self::PasswordAlt => 'heroicon-m-key',
            default => 'heroicon-m-question-mark-circle',
        };
    }

    /**
     * Get a human-readable name for any verify mode value, including unknown ones.
     */
    public static function nameFor(int $mode): string
    {
        $instance = self::tryFrom($mode);

        return $instance ? $instance->getLabel() : "Unknown ({$mode})";
    }
}
