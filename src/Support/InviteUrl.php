<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Support;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\URL;
use SensitiveParameter;

/**
 * The set-password link: signed, expiring with the token, naming the account by email.
 *
 * Signed RELATIVE (as the source application does) so that a proxy rewriting the
 * scheme or host does not break the signature; `app.url` supplies the origin.
 */
final class InviteUrl
{
    public const string ROUTE = 'provisioning.accept';

    public static function for(CanResetPassword $user, #[SensitiveParameter] string $token): string
    {
        $relative = URL::temporarySignedRoute(
            (string) config('provisioning.invite.route', self::ROUTE),
            now()->addMinutes((int) config('provisioning.broker.expire', 10080)),
            ['token' => $token, 'email' => $user->getEmailForPasswordReset()],
            absolute: false,
        );

        return rtrim((string) config('app.url'), '/').$relative;
    }
}
