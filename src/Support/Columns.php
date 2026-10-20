<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Support;

/**
 * The users-table column names, read from configuration every time — never cached in
 * a property, so a host that changes them at runtime (or a test) is honoured.
 */
final class Columns
{
    public static function forcePasswordChange(): string
    {
        return self::name('force_password_change');
    }

    public static function passwordChangedAt(): string
    {
        return self::name('password_changed_at');
    }

    public static function lastLoginAt(): string
    {
        return self::name('last_login_at');
    }

    public static function loginCount(): string
    {
        return self::name('login_count');
    }

    public static function twoFactorSecret(): string
    {
        return self::name('two_factor_secret');
    }

    public static function twoFactorRecoveryCodes(): string
    {
        return self::name('two_factor_recovery_codes');
    }

    private static function name(string $key): string
    {
        $name = config("provisioning.columns.{$key}");

        return is_string($name) && $name !== '' ? $name : $key;
    }
}
