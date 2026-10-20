<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * The fixture user, plus what a Filament panel requires to let it in. Kept apart from
 * User so the core suite never loads a Filament contract.
 */
class BridgeUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
