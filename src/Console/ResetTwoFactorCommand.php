<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Rolland\Provisioning\Actions\ResetTwoFactor;
use Rolland\Provisioning\Support\Columns;

/**
 * The escape hatch for a lockout nobody can sign in to fix — the last administrator's
 * own authenticator lost. Same action as the admin button, with no causer.
 */
final class ResetTwoFactorCommand extends Command
{
    protected $signature = 'provisioning:reset-two-factor {email : The account\'s email address}';

    protected $description = 'Reset a user\'s two-factor authentication (lockout escape hatch)';

    public function handle(ResetTwoFactor $resetTwoFactor): int
    {
        $email = $this->argument('email');
        $email = is_string($email) ? $email : '';

        $user = Auth::createUserProvider((string) config('provisioning.broker.provider', 'users'))
            ?->retrieveByCredentials(['email' => $email]);

        if (! $user instanceof Model || ! $user instanceof Authenticatable) {
            $this->error((string) __('provisioning::provisioning.two_factor.console_unknown', ['email' => $email]));

            return self::FAILURE;
        }

        if (blank($user->getAttribute(Columns::twoFactorSecret()))) {
            $this->info((string) __('provisioning::provisioning.two_factor.console_not_enrolled', ['email' => $email]));

            return self::SUCCESS;
        }

        $resetTwoFactor->handle($user, null);

        $this->info((string) __('provisioning::provisioning.two_factor.console_reset', ['email' => $email]));

        return self::SUCCESS;
    }
}
