<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Actions;

use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Support\Columns;

/**
 * Arm the forced change — call it whenever someone other than the person sets their
 * password. Never call it for a password the person chose (constitution IV).
 */
final class ArmForcedPasswordChange
{
    public function handle(Model $user): void
    {
        $user->forceFill([Columns::forcePasswordChange() => true])->save();
    }
}
