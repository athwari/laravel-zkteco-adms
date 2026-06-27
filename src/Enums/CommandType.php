<?php

namespace Athwari\LaravelZktecoAdms\Enums;

enum CommandType: string
{
    case Info = 'INFO';

    case Reboot = 'REBOOT';

    case Clear = 'CLEAR';

    case Data = 'DATA';

    case Check = 'CHECK';

    public function getLabel(): string
    {
        return __('filament-zkteco-adms::default.enums.command_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Reboot => 'warning',
            self::Clear => 'danger',
            self::Data => 'primary',
            self::Check => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Info => 'heroicon-m-information-circle',
            self::Reboot => 'heroicon-m-arrow-path',
            self::Clear => 'heroicon-m-trash',
            self::Data => 'heroicon-m-circle-stack',
            self::Check => 'heroicon-m-signal',
        };
    }
}
