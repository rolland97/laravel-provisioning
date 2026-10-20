<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported from the source application's RecordSuccessfulLoginTest (FR-013): a
 * successful sign-in stamps the time and counts it, in ONE update; a failed one
 * records nothing. (The abandoned two-factor challenge case is Filament's login and
 * lives in the bridge suite.)
 */

function signingInUser(): User
{
    return User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'the-right-password']);
}

it('stamps the time and counts the first sign-in, in a single update', function (): void {
    $user = signingInUser()->refresh();
    expect($user->last_login_at)->toBeNull()->and($user->login_count)->toBe(0);

    DB::enableQueryLog();
    Auth::attempt(['email' => 'ada@example.test', 'password' => 'the-right-password']);
    $updates = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_starts_with(strtolower($query['query']), 'update'))->values();
    DB::disableQueryLog();

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull()
        ->and($user->login_count)->toBe(1)
        ->and($updates)->toHaveCount(1);
});

it('counts every successful sign-in', function (): void {
    signingInUser();

    Auth::attempt(['email' => 'ada@example.test', 'password' => 'the-right-password']);
    Auth::logout();
    Auth::attempt(['email' => 'ada@example.test', 'password' => 'the-right-password']);

    expect(User::query()->firstOrFail()->login_count)->toBe(2);
});

it('records nothing on a failed password', function (): void {
    signingInUser();

    Auth::attempt(['email' => 'ada@example.test', 'password' => 'wrong']);

    $user = User::query()->firstOrFail();
    expect($user->last_login_at)->toBeNull()->and($user->login_count)->toBe(0);
});

it('records nothing when the host turned it off', function (): void {
    config(['provisioning.record_logins' => false]);
    signingInUser();

    Auth::attempt(['email' => 'ada@example.test', 'password' => 'the-right-password']);

    expect(User::query()->firstOrFail()->login_count)->toBe(0);
});
