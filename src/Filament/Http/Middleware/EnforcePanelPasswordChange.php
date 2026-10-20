<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament\Http\Middleware;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Rolland\Provisioning\Filament\Pages\ForcePasswordChange;
use Rolland\Provisioning\Http\Middleware\EnforcePasswordChange;

/**
 * The trap inside a Filament panel: the panel's guard, the panel's own change page,
 * and Filament's routes that must stay reachable — sign-out and two-factor set-up
 * (which completes first) — on top of the configured exceptions.
 */
final class EnforcePanelPasswordChange extends EnforcePasswordChange
{
    protected function user(Request $request): ?Authenticatable
    {
        return Filament::auth()->user();
    }

    protected function exceptions(): array
    {
        return [
            ...parent::exceptions(),
            // Livewire's update route name is configurable (`default-livewire.update`
            // in some applications), so any prefix.
            '*livewire.update',
            'filament.*.auth.logout',
            'filament.*.pages.force-password-change',
            'filament.*.auth.multi-factor-authentication.*',
        ];
    }

    protected function changePageUrl(): string
    {
        return ForcePasswordChange::getUrl(panel: Filament::getCurrentOrDefaultPanel()?->getId());
    }
}
