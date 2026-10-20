<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Defaults;

use Illuminate\Contracts\Auth\Authenticatable;
use Rolland\Provisioning\Contracts\HandlesPasswordChange;
use SensitiveParameter;

final class NoopPasswordChange implements HandlesPasswordChange
{
    public function passwordChanged(Authenticatable $user, #[SensitiveParameter] string $newPassword): void {}
}
