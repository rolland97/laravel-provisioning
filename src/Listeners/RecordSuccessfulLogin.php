<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Support\Columns;

/**
 * Stamp the sign-in time and count it — one UPDATE, the counter and the timestamp
 * together. `Login` fires only once authentication is complete, so a failed password
 * or an abandoned two-factor challenge records nothing.
 */
final class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! config('provisioning.record_logins', true) || ! $event->user instanceof Model) {
            return;
        }

        $event->user->increment(Columns::loginCount(), 1, [Columns::lastLoginAt() => now()]);
    }
}
