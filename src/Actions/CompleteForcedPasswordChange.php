<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Contracts\HandlesPasswordChange;
use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Support\Columns;
use SensitiveParameter;

/**
 * The person replaces a password someone else set. Validating the new password's
 * strength and confirmation is the caller's job (the Filament page, or the host's
 * form); this refuses only what must be refused whoever calls it.
 */
final class CompleteForcedPasswordChange
{
    public function __construct(
        private readonly HandlesPasswordChange $passwordChanged,
        private readonly DecidesEnforcementSkip $enforcementSkip,
    ) {}

    /**
     * @throws ValidationException when the session may not complete it, or the new
     *                             password is the current one
     */
    public function handle(Authenticatable&Model $user, #[SensitiveParameter] string $newPassword): void
    {
        // e.g. an impersonator: the change waits for the account's owner.
        if ($this->enforcementSkip->blocksCompletion($user)) {
            throw ValidationException::withMessages([
                'password' => __('provisioning::provisioning.force_change.helper'),
            ]);
        }

        if (Hash::check($newPassword, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => __('provisioning::provisioning.force_change.same_as_current'),
            ]);
        }

        $user->forceFill([
            $user->getAuthPasswordName() => Hash::make($newPassword),
            Columns::forcePasswordChange() => false,
            Columns::passwordChangedAt() => now(),
        ])->save();

        $this->passwordChanged->passwordChanged($user, $newPassword);

        event(new ForcedPasswordChangeCompleted($user->getAuthIdentifier()));
    }
}
