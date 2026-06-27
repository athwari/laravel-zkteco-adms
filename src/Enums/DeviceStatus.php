<?php

namespace Athwari\LaravelZktecoAdms\Enums;

enum DeviceStatus: string
{
    case Online = 'online';

    case Offline = 'offline';

    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return __('filament-zkteco-adms::default.enums.device_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Online => 'success',
            self::Offline => 'danger',
            self::Unknown => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Online => 'heroicon-m-signal',
            self::Offline => 'heroicon-m-signal-slash',
            self::Unknown => 'heroicon-m-question-mark-circle',
        };
    }
}
