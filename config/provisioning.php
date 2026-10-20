<?php

declare(strict_types=1);

use Rolland\Provisioning\Defaults\LaravelPasswordRules;
use Rolland\Provisioning\Defaults\NeverSkipEnforcement;
use Rolland\Provisioning\Defaults\NoopPasswordChange;

return [

    /*
    | Column names on the users table. Rename here and in the published migration
    | together. The two-factor columns default to Filament 5's names; another 2FA
    | provider (Fortify: two_factor_secret, two_factor_recovery_codes) differs.
    */
    'columns' => [
        'force_password_change' => 'force_password_change',
        'password_changed_at' => 'password_changed_at',
        'last_login_at' => 'last_login_at',
        'login_count' => 'login_count',
        'two_factor_secret' => 'app_authentication_secret',
        'two_factor_recovery_codes' => 'app_authentication_recovery_codes',
    ],

    /*
    | The invite broker. The package registers it under `auth.passwords.{name}` at
    | boot unless the application already defines that name. ⚠️ Keep `table` apart
    | from the password-reset table: the two flows would evict each other's tokens.
    | `expire` is in minutes (default 7 days) and also bounds the signed invite link.
    */
    'broker' => [
        'name' => 'provisioning',
        'provider' => 'users',
        'table' => 'provisioning_tokens',
        'expire' => 10080,
        'throttle' => 60,
    ],

    'invite' => [
        // Path of the accept-invite page. With the Filament plugin it sits under the
        // accepting panel's path, and `route` is set to that panel's route name.
        'path' => 'accept-invite/{token}',
        // Route name the invite link points at. Without Filament, name your own
        // accept route this (it receives `token` and a signed `email`).
        'route' => 'provisioning.accept',
        // Mail view; publish `provisioning-views` to restyle, or point at your own.
        'mail_view' => 'provisioning::mail.invite',
    ],

    /*
    | The trap for a forced password change (core middleware). Requests whose route
    | name matches an `except` pattern pass; others redirect to `redirect_route`.
    | The Filament plugin adds its panel's own exemptions on top of these.
    */
    'enforcement' => [
        'except' => [
            '*livewire.update',
            'logout',
        ],
        'redirect_route' => null,
    ],

    /*
    | Host policy, as classes the container resolves. Each default is the safe choice.
    */
    'hooks' => [
        // After a forced change completes — e.g. end the person's other sessions.
        'password_changed' => NoopPasswordChange::class,
        // Whether a request skips the trap (e.g. a permitted impersonation), and
        // whether such a session is refused completion of the change.
        'enforcement_skip' => NeverSkipEnforcement::class,
        // The password rules for the invite and change pages, and an optional view
        // showing them.
        'password_rules' => LaravelPasswordRules::class,
    ],

    // Record provisioning events in spatie/laravel-activitylog when it is installed.
    'activity_log' => true,

    // Stamp last_login_at and increment login_count on each successful sign-in.
    'record_logins' => true,

];
