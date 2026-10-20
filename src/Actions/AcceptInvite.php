<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;
use SensitiveParameter;

/**
 * Accept an invite: the broker checks the address, the token and its age, then the
 * password is set. Returns false — changing nothing — for an unknown address or an
 * invalid, used, superseded or expired token. Validating the password itself (rules,
 * confirmation) is the caller's job: the Filament page, or the host's own form.
 */
final class AcceptInvite
{
    public function __construct(private readonly CompleteProvisioning $completeProvisioning) {}

    public function handle(string $email, #[SensitiveParameter] string $token, #[SensitiveParameter] string $password): bool
    {
        $status = Password::broker((string) config('provisioning.broker.name', 'provisioning'))->reset(
            ['email' => $email, 'password' => $password, 'token' => $token],
            function (Authenticatable&Model $user) use ($password): void {
                $this->completeProvisioning->handle($user, $password);
            },
        );

        return $status === Password::PASSWORD_RESET;
    }
}
