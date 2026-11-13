<?php

declare(strict_types=1);

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * 002 FR-001–FR-003: a completed password reset is the person's own password, so it
 * clears the forced change. Laravel dispatches `PasswordReset` only after a successful
 * reset; the broker cases below prove a failed one reaches nothing.
 */

function resettingUser(bool $armed): User
{
    return User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1', 'force_password_change' => $armed]);
}

it('clears an armed requirement, stamps the time and reports the completion once', function (): void {
    Event::fake([ForcedPasswordChangeCompleted::class]);
    $this->freezeTime();
    $user = resettingUser(armed: true);

    event(new PasswordReset($user));

    $user->refresh();
    expect($user->force_password_change)->toBeFalse()
        ->and($user->password_changed_at?->toDateTimeString())->toBe(now()->toDateTimeString());
    Event::assertDispatchedTimes(ForcedPasswordChangeCompleted::class, 1);
    Event::assertDispatched(ForcedPasswordChangeCompleted::class, fn (ForcedPasswordChangeCompleted $event): bool => $event->userId === $user->getKey());
});

it('stamps the time for a person who was not armed, and reports no completion', function (): void {
    Event::fake([ForcedPasswordChangeCompleted::class]);
    $this->freezeTime();
    $user = resettingUser(armed: false);

    event(new PasswordReset($user));

    $user->refresh();
    expect($user->force_password_change)->toBeFalse()
        ->and($user->password_changed_at?->toDateTimeString())->toBe(now()->toDateTimeString());
    Event::assertNotDispatched(ForcedPasswordChangeCompleted::class);
});

it('leaves the requirement alone when switched off', function (): void {
    config(['provisioning.clear_on_password_reset' => false]);
    $user = resettingUser(armed: true);

    event(new PasswordReset($user));

    expect($user->refresh()->force_password_change)->toBeTrue()
        ->and($user->password_changed_at)->toBeNull();
});

it('clears the requirement through a real broker reset with a valid token', function (): void {
    $user = resettingUser(armed: true);
    $token = Password::broker()->createToken($user);

    $status = Password::broker()->reset(
        ['email' => 'ada@example.test', 'token' => $token, 'password' => 'my-own-new-secret-1', 'password_confirmation' => 'my-own-new-secret-1'],
        function (User $user, string $password): void {
            $user->forceFill(['password' => $password])->save();
            event(new PasswordReset($user));
        },
    );

    expect($status)->toBe(Password::PASSWORD_RESET)
        ->and($user->refresh()->force_password_change)->toBeFalse();
});

it('changes nothing when the token is wrong', function (): void {
    $user = resettingUser(armed: true);
    Password::broker()->createToken($user);

    $status = Password::broker()->reset(
        ['email' => 'ada@example.test', 'token' => 'not-the-token', 'password' => 'my-own-new-secret-1', 'password_confirmation' => 'my-own-new-secret-1'],
        function (User $user, string $password): void {
            $user->forceFill(['password' => $password])->save();
            event(new PasswordReset($user));
        },
    );

    expect($status)->toBe(Password::INVALID_TOKEN)
        ->and($user->refresh()->force_password_change)->toBeTrue()
        ->and($user->password_changed_at)->toBeNull();
});
