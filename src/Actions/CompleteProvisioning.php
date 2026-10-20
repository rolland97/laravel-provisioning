<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Rolland\Provisioning\Events\InviteAccepted;
use Rolland\Provisioning\Support\Columns;
use SensitiveParameter;

/**
 * The person sets their own password from an invite. ⚠️ A password the person chose
 * never arms the forced change — this clears it explicitly (constitution IV).
 */
final class CompleteProvisioning
{
    public function handle(Authenticatable&Model $user, #[SensitiveParameter] string $password): void
    {
        $user->forceFill([
            $user->getAuthPasswordName() => Hash::make($password),
            $user->getRememberTokenName() => Str::random(60),
            Columns::forcePasswordChange() => false,
        ])->save();

        event(new InviteAccepted($user->getAuthIdentifier()));
    }
}
