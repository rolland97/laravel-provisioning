<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Events\TwoFactorReset;
use Rolland\Provisioning\Support\Columns;

/**
 * Clear a two-factor enrolment so the person enrols again at their next sign-in.
 *
 * ONE code path for the administrator action and the console escape hatch
 * (constitution IV): a null causer means the console. The event carries ids and the
 * route taken — never the secret or the codes (constitution II).
 */
final class ResetTwoFactor
{
    public function handle(Model&Authenticatable $user, ?Authenticatable $causer): void
    {
        $user->forceFill([
            Columns::twoFactorSecret() => null,
            Columns::twoFactorRecoveryCodes() => null,
        ])->save();

        event(new TwoFactorReset(
            $user->getAuthIdentifier(),
            $causer?->getAuthIdentifier(),
            $causer instanceof Authenticatable ? TwoFactorReset::VIA_ADMIN : TwoFactorReset::VIA_CONSOLE,
        ));
    }
}
