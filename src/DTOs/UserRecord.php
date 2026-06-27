<?php

declare(strict_types=1);

namespace Athwari\LaravelZktecoAdms\DTOs;

/**
 * Represents a user record returned by the device in response to
 * a DATA QUERY USERINFO command.
 *
 * The device pushes user data via POST /iclock/cdata with
 * tab-separated key=value fields.
 */
final class UserRecord
{
    public function __construct(
        /** The user's personal identification number (unique on the device). */
        public string $pin,
        /** The user's display name. */
        public string $name,
        /** The user's privilege level (0 = normal, 14 = admin). */
        public int $privilege,
        /** The user's RFID card number, if any. */
        public string $card,
        /** The user's password, if any. */
        public string $password,
    ) {}

    /**
     * Whether this user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->privilege === 14;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pin' => $this->pin,
            'name' => $this->name,
            'privilege' => $this->privilege,
            'card' => $this->card,
            'password' => $this->password,
        ];
    }
}
