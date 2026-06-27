<?php

namespace Athwari\LaravelZktecoAdms\Enums;

enum UserPrivilege: int
{
    case User = 0;

    case Admin = 14;

    public function getLabel(): string
    {
        return __('filament-zkteco-adms::default.enums.user_privilege.'.$this->labelKey());
    }

    public function getColor(): string
    {
        return match ($this) {
            self::User => 'gray',
            self::Admin => 'danger',
        };
    }

    /**
     * Get a human-readable name for any privilege value, including unknown ones.
     */
    public static function nameFor(int $value): string
    {
        $instance = self::tryFrom($value);

        return $instance ? $instance->getLabel() : "Unknown ({$value})";
    }

    private function labelKey(): string
    {
        return match ($this) {
            self::User => 'user',
            self::Admin => 'admin',
        };
    }
}
