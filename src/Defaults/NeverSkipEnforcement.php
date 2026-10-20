<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Defaults;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;

final class NeverSkipEnforcement implements DecidesEnforcementSkip
{
    public function shouldSkip(Request $request, Authenticatable $user): bool
    {
        return false;
    }

    public function blocksCompletion(Authenticatable $user): bool
    {
        return false;
    }
}
