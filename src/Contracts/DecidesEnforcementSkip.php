<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Lets the host exempt a request from the forced-change trap — the source application
 * exempts a permitted impersonation, leaving the change for the account's owner.
 */
interface DecidesEnforcementSkip
{
    /** Whether this request passes the trap although the change is armed. */
    public function shouldSkip(Request $request, Authenticatable $user): bool;

    /** Whether the current session must be refused completion of the change. */
    public function blocksCompletion(Authenticatable $user): bool;
}
