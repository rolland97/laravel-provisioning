<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;
use Rolland\Provisioning\Filament\Http\Middleware\EnforcePanelPasswordChange;
use Rolland\Provisioning\Filament\Pages\AcceptInvite;
use Rolland\Provisioning\Filament\Pages\ForcePasswordChange;

/**
 * The panel plugin. Touches only the panel that registers it (constitution V):
 *
 *     $panel->plugin(ProvisioningPlugin::make()->forcePasswordChange()->acceptsInvites());
 *
 * `forcePasswordChange()` adds the change page and the trap, as a PERSISTENT auth
 * middleware so a flag set mid-session traps on the next Livewire round trip too.
 * `acceptsInvites()` adds the accept-invite page under this panel's path and points
 * invite links at it — register it on one panel.
 */
final class ProvisioningPlugin implements Plugin
{
    private bool $forcePasswordChange = false;

    private bool $acceptsInvites = false;

    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'provisioning';
    }

    public function forcePasswordChange(bool $condition = true): self
    {
        $this->forcePasswordChange = $condition;

        return $this;
    }

    public function acceptsInvites(bool $condition = true): self
    {
        $this->acceptsInvites = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if ($this->forcePasswordChange) {
            $panel
                ->pages([ForcePasswordChange::class])
                ->authMiddleware([EnforcePanelPasswordChange::class], isPersistent: true);
        }

        if ($this->acceptsInvites) {
            config(['provisioning.invite.route' => "filament.{$panel->getId()}.provisioning.accept"]);

            $panel->routes(function (): void {
                Route::get((string) config('provisioning.invite.path', 'accept-invite/{token}'), AcceptInvite::class)
                    ->middleware(ValidateSignature::relative())
                    ->name('provisioning.accept');
            });
        }
    }

    public function boot(Panel $panel): void {}
}
