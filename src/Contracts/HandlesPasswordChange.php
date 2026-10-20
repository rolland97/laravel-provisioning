<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use SensitiveParameter;

/**
 * Runs after a forced password change completes. The source application ends the
 * person's other sessions here; the default does nothing.
 */
interface HandlesPasswordChange
{
    public function passwordChanged(Authenticatable $user, #[SensitiveParameter] string $newPassword): void;
}
