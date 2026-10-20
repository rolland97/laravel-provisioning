<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Rolland\Provisioning\Filament\Actions\ResetTwoFactorAction;
use Rolland\Provisioning\Filament\Actions\SendInviteAction;
use Rolland\Provisioning\Notifications\ProvisioningInvite;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;

/*
 * The admin buttons from the source application's EditUser page: re-send an invite
 * only before the first sign-in; reset two-factor only when enrolled.
 */

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = BridgeUser::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'whatever-123']);
    $this->actingAs($this->admin);
});

function actionTarget(array $attributes = []): BridgeUser
{
    return BridgeUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123', ...$attributes]);
}

it('offers the invite only to someone who has never signed in, and sends it', function (): void {
    Notification::fake();
    $never = actionTarget();
    $seen = BridgeUser::query()->create(['name' => 'Bea', 'email' => 'bea@example.test', 'password' => 'x-123456789', 'last_login_at' => now()]);

    expect(SendInviteAction::make()->record($never)->isVisible())->toBeTrue()
        ->and(SendInviteAction::make()->record($seen)->isVisible())->toBeFalse();

    SendInviteAction::make()->record($never)->call();

    Notification::assertSentTo($never, ProvisioningInvite::class);
});

it('offers a two-factor reset only when the account is enrolled, and resets it', function (): void {
    $enrolled = actionTarget(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $plain = BridgeUser::query()->create(['name' => 'Bea', 'email' => 'bea@example.test', 'password' => 'x-123456789']);

    expect(ResetTwoFactorAction::make()->record($enrolled)->isVisible())->toBeTrue()
        ->and(ResetTwoFactorAction::make()->record($plain)->isVisible())->toBeFalse();

    ResetTwoFactorAction::make()->record($enrolled)->call();

    expect($enrolled->refresh()->app_authentication_secret)->toBeNull();
});
