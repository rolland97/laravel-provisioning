<?php

declare(strict_types=1);

use Rolland\Provisioning\Actions\ArmForcedPasswordChange;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported in intent from the source application's ArmForcePasswordChangeTest (FR-007):
 * there, arming lives in the Create/Edit user pages; here it is an explicit action
 * the host (or the Filament plugin's actions) calls when someone else sets a password.
 */

it('arms the forced change', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1']);

    expect($user->refresh()->force_password_change)->toBeFalse('The stub default is not false, so this test proves nothing.');

    app(ArmForcedPasswordChange::class)->handle($user);

    expect($user->refresh()->force_password_change)->toBeTrue();
});

it('defaults to unarmed, so installing the package locks nobody out', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123']);

    expect($user->refresh()->force_password_change)->toBeFalse();
});
