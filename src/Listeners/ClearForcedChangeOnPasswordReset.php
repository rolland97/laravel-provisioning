<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Support\Columns;

/**
 * A completed password reset is the person's own password, so it does what the forced
 * change asks (002 FR-001). Laravel dispatches `PasswordReset` only after the broker has
 * accepted the token and the new password is saved, so an expired, used or wrong link
 * never reaches this. Without it, a person whose password an administrator set would
 * choose a password through the reset link and then be told to choose one again.
 */
final class ClearForcedChangeOnPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        if (! config('provisioning.clear_on_password_reset', true) || ! $event->user instanceof Model) {
            return;
        }

        $user = $event->user;
        $wasArmed = (bool) $user->getAttribute(Columns::forcePasswordChange());

        $user->forceFill([
            Columns::forcePasswordChange() => false,
            Columns::passwordChangedAt() => now(),
        ])->save();

        if ($wasArmed) {
            event(new ForcedPasswordChangeCompleted($user->getKey()));
        }
    }
}
