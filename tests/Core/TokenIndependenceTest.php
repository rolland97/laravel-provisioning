<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Password;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported from the source application's TokenIndependenceTest (FR-002, constitution II).
 * The framework's token repository deletes an identity's row before inserting, so two
 * flows on one table hold ONE live token between them.
 */

function independentUser(): User
{
    return User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123']);
}

it('keeps a pending invite alive when a reset is requested', function (): void {
    $user = independentUser();
    $invite = Password::broker('provisioning')->getRepository()->create($user);

    expect(Password::broker('provisioning')->getRepository()->exists($user, $invite))
        ->toBeTrue('The invite was not created, so this test proves nothing.');

    Password::broker('users')->getRepository()->create($user);

    expect(Password::broker('provisioning')->getRepository()->exists($user, $invite))
        ->toBeTrue('Requesting a reset deleted a pending invite — the two flows share one table.');
});

it('keeps a pending reset alive when an invite is re-issued', function (): void {
    $user = independentUser();
    $reset = Password::broker('users')->getRepository()->create($user);

    expect(Password::broker('users')->getRepository()->exists($user, $reset))
        ->toBeTrue('The reset token was not created, so this test proves nothing.');

    Password::broker('provisioning')->getRepository()->create($user);

    expect(Password::broker('users')->getRepository()->exists($user, $reset))
        ->toBeTrue('Re-issuing an invite deleted a pending reset link — the two flows share one table.');
});

it('keeps the two flows on different tables', function (): void {
    expect(config('auth.passwords.provisioning.table'))->not->toBe(config('auth.passwords.users.table'));
});
