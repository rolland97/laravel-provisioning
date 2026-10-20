<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests\Fixtures;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A host's user model as plainly as Laravel ships it — no package trait, no Filament.
 * The package must work against this, not against a model shaped for it.
 */
class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    use Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'force_password_change' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }
}
