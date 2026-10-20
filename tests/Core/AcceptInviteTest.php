<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Rolland\Provisioning\Actions\AcceptInvite;
use Rolland\Provisioning\Events\InviteAccepted;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported from the source application's AcceptProvisioningInviteTest — the core half
 * (the action). The page half (validation, confirmation, redirect) is in the bridge
 * suite. FR-002, FR-003, FR-004.
 */

function acceptingUser(array $attributes = []): User
{
    return User::query()->create([
        'name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'placeholder-'.bin2hex(random_bytes(8)),
        'remember_token' => 'the-old-remember-token', ...$attributes,
    ]);
}

it('sets the password from a valid invite, never arms the forced change, and consumes the token', function (): void {
    Event::fake([InviteAccepted::class]);
    $user = acceptingUser(['force_password_change' => false]);
    $token = Password::broker('provisioning')->getRepository()->create($user);

    $accepted = app(AcceptInvite::class)->handle($user->email, $token, 'a-brand-new-secret-1');

    $user->refresh();

    expect($accepted)->toBeTrue()
        ->and(Hash::check('a-brand-new-secret-1', $user->password))->toBeTrue()
        ->and($user->force_password_change)->toBeFalse()
        ->and($user->remember_token)->not->toBe('the-old-remember-token')
        ->and(Password::broker('provisioning')->tokenExists($user, $token))->toBeFalse();

    Event::assertDispatched(InviteAccepted::class, fn (InviteAccepted $event): bool => $event->userId === $user->getKey());
});

it('clears a forced change that was armed before the person chose their own password', function (): void {
    $user = acceptingUser(['force_password_change' => true]);
    $token = Password::broker('provisioning')->getRepository()->create($user);

    app(AcceptInvite::class)->handle($user->email, $token, 'a-brand-new-secret-1');

    expect($user->refresh()->force_password_change)->toBeFalse();
});

it('refuses an unknown token and changes nothing', function (): void {
    $user = acceptingUser();

    expect(app(AcceptInvite::class)->handle($user->email, 'bogus-token', 'a-brand-new-secret-1'))->toBeFalse()
        ->and(Hash::check('a-brand-new-secret-1', $user->refresh()->password))->toBeFalse();
});

it('refuses a token used once already', function (): void {
    $user = acceptingUser();
    $token = Password::broker('provisioning')->getRepository()->create($user);

    expect(app(AcceptInvite::class)->handle($user->email, $token, 'a-brand-new-secret-1'))->toBeTrue()
        ->and(app(AcceptInvite::class)->handle($user->email, $token, 'a-second-attempt-99'))->toBeFalse()
        ->and(Hash::check('a-second-attempt-99', $user->refresh()->password))->toBeFalse();
});

it('refuses a token superseded by a newer invite', function (): void {
    $user = acceptingUser();
    $old = Password::broker('provisioning')->getRepository()->create($user);
    Password::broker('provisioning')->getRepository()->create($user);

    expect(app(AcceptInvite::class)->handle($user->email, $old, 'a-brand-new-secret-1'))->toBeFalse();
});

it('refuses an expired token', function (): void {
    $user = acceptingUser();
    $token = Password::broker('provisioning')->getRepository()->create($user);

    $this->travel(config('provisioning.broker.expire') + 1)->minutes();

    expect(app(AcceptInvite::class)->handle($user->email, $token, 'a-brand-new-secret-1'))->toBeFalse();
});

it('refuses a token presented with another person\'s address', function (): void {
    $user = acceptingUser();
    $other = acceptingUser(['email' => 'eve@example.test']);
    $token = Password::broker('provisioning')->getRepository()->create($user);

    expect(app(AcceptInvite::class)->handle($other->email, $token, 'a-brand-new-secret-1'))->toBeFalse()
        ->and(Hash::check('a-brand-new-secret-1', $other->refresh()->password))->toBeFalse()
        ->and(Hash::check('a-brand-new-secret-1', $user->refresh()->password))->toBeFalse();
});
