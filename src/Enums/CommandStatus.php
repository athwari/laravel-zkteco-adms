<?php

namespace Athwari\LaravelZktecoAdms\Enums;

enum CommandStatus: string
{
    case Pending = 'pending';

    case Sent = 'sent';

    case Acknowledged = 'acknowledged';

    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('filament-zkteco-adms::default.enums.command_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Sent => 'info',
            self::Acknowledged => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Sent => 'heroicon-m-paper-airplane',
            self::Acknowledged => 'heroicon-m-check-circle',
            self::Failed => 'heroicon-m-x-circle',
        };
    }
}
