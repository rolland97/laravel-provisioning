<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Actions;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;
use Rolland\Provisioning\Events\InviteResent;
use Rolland\Provisioning\Events\InviteSent;
use Rolland\Provisioning\Notifications\ProvisioningInvite;

/**
 * Issue an invite: a fresh single-use token (which replaces any earlier one — the
 * repository keeps one row per address) and a queued mail. No password is sent.
 */
final class SendInvite
{
    public function handle(Authenticatable&CanResetPassword $user, ?Authenticatable $causer, bool $resend = false): void
    {
        if (! method_exists($user, 'notify')) {
            throw new InvalidArgumentException('The user model must use the Notifiable trait to receive an invite.');
        }

        $broker = Password::broker((string) config('provisioning.broker.name', 'provisioning'));

        // The token repository lives on the concrete broker, not on its contract.
        if (! $broker instanceof PasswordBroker) {
            throw new InvalidArgumentException('The provisioning broker must be a token-repository password broker.');
        }

        $token = $broker->getRepository()->create($user);

        $user->notify(new ProvisioningInvite($token));

        event($resend
            ? new InviteResent($user->getAuthIdentifier(), $causer?->getAuthIdentifier())
            : new InviteSent($user->getAuthIdentifier(), $causer?->getAuthIdentifier()));
    }
}
