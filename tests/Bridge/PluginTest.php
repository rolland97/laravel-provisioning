<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Rolland\Provisioning\Filament\Http\Middleware\EnforcePanelPasswordChange;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;

/*
 * US4 / SC-003: the plugin adds the change page, the persistent trap and the
 * accept-invite page to the panel that registers it — and nothing to one that does
 * not.
 */

function bridgeUser(array $attributes = []): BridgeUser
{
    return BridgeUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1', ...$attributes]);
}

it('adds the change page and the accept-invite route to the panel with the plugin only', function (): void {
    expect(Route::has('filament.admin.pages.force-password-change'))->toBeTrue()
        ->and(Route::has('filament.admin.provisioning.accept'))->toBeTrue()
        ->and(Route::has('filament.plain.pages.force-password-change'))->toBeFalse()
        ->and(Route::has('filament.plain.provisioning.accept'))->toBeFalse();
});

it('points the invite link at the accepting panel', function (): void {
    expect(config('provisioning.invite.route'))->toBe('filament.admin.provisioning.accept');
});

it('traps an armed user on the plugin panel and not on the plain one', function (): void {
    $user = bridgeUser(['force_password_change' => true]);

    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/force-password-change');
    $this->actingAs($user)->get('/plain')->assertOk();
});

it('registers the trap as persistent, so Livewire round trips run it', function (): void {
    expect(Livewire::getPersistentMiddleware())->toContain(EnforcePanelPasswordChange::class);
});

it('keeps the change page and sign-out reachable while armed', function (): void {
    $user = bridgeUser(['force_password_change' => true]);

    $this->actingAs($user)->get('/admin/force-password-change')->assertOk();
    $this->actingAs($user)->post(Filament::getPanel('admin')->getLogoutUrl())->assertRedirect();
});
