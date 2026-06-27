<?php

namespace Athwari\LaravelZktecoAdms\Enums;

enum DeviceEventType: string
{
    case Registered = 'registered';

    case Connected = 'connected';

    case Disconnected = 'disconnected';

    case InfoReceived = 'info_received';

    case CommandSent = 'command_sent';

    case CommandAcknowledged = 'command_acknowledged';

    case AttendanceSynced = 'attendance_synced';

    case UserSynced = 'user_synced';

    case StatusChanged = 'status_changed';

    public function getLabel(): string
    {
        return __('filament-zkteco-adms::default.enums.device_event_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Registered => 'primary',
            self::Connected => 'success',
            self::Disconnected => 'danger',
            self::InfoReceived => 'info',
            self::CommandSent => 'warning',
            self::CommandAcknowledged => 'success',
            self::AttendanceSynced => 'info',
            self::UserSynced => 'primary',
            self::StatusChanged => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Registered => 'heroicon-m-plus',
            self::Connected => 'heroicon-m-signal',
            self::Disconnected => 'heroicon-m-signal-slash',
            self::InfoReceived => 'heroicon-m-information-circle',
            self::CommandSent => 'heroicon-m-paper-airplane',
            self::CommandAcknowledged => 'heroicon-m-check-circle',
            self::AttendanceSynced => 'heroicon-m-clipboard-document-check',
            self::UserSynced => 'heroicon-m-user-group',
            self::StatusChanged => 'heroicon-m-arrow-path',
        };
    }
}
