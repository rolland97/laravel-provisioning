<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Rolland\Provisioning\Actions\ResetTwoFactor;
use Rolland\Provisioning\Events\TwoFactorReset;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported from the source application's ResetTwoFactorTest (FR-012, constitution II
 * and IV): one code path for the admin action and the console escape hatch, recording
 * who and how, and never the secret.
 */

function enrolledUser(string $email = 'ada@example.test'): User
{
    return User::query()->create([
        'name' => 'Ada', 'email' => $email, 'password' => 'whatever-123',
        'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        'app_authentication_recovery_codes' => [Hash::make('aaaaaaaaaa-bbbbbbbbbb')],
    ]);
}

it('clears the secret and recovery codes and reports the admin who did it', function (): void {
    Event::fake([TwoFactorReset::class]);
    $user = enrolledUser();
    $admin = enrolledUser('admin@example.test');

    app(ResetTwoFactor::class)->handle($user, $admin);

    $user->refresh();
    expect($user->app_authentication_secret)->toBeNull()
        ->and($user->app_authentication_recovery_codes)->toBeNull();

    Event::assertDispatched(TwoFactorReset::class, fn (TwoFactorReset $event): bool => $event->userId === $user->getKey()
        && $event->causerId === $admin->getKey()
        && $event->via === TwoFactorReset::VIA_ADMIN);
});

it('records a reset with no causer as coming from the console, carrying no secret', function (): void {
    $dispatched = [];
    Event::listen(TwoFactorReset::class, function (TwoFactorReset $event) use (&$dispatched): void {
        $dispatched[] = $event;
    });

    app(ResetTwoFactor::class)->handle(enrolledUser(), null);

    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0]->causerId)->toBeNull()
        ->and($dispatched[0]->via)->toBe(TwoFactorReset::VIA_CONSOLE)
        ->and(serialize($dispatched[0]))->not->toContain('JBSWY3DPEHPK3PXP');
});

it('uses the column names the host configured', function (): void {
    Schema::table('users', function ($table): void {
        $table->text('two_factor_secret')->nullable();
        $table->text('two_factor_recovery_codes')->nullable();
    });
    config([
        'provisioning.columns.two_factor_secret' => 'two_factor_secret',
        'provisioning.columns.two_factor_recovery_codes' => 'two_factor_recovery_codes',
    ]);

    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123']);
    $user->forceFill(['two_factor_secret' => 'FORTIFYSECRET', 'two_factor_recovery_codes' => 'codes'])->save();

    app(ResetTwoFactor::class)->handle($user, null);

    expect($user->refresh()->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull();
});

it('resets by email from the console', function (): void {
    $user = enrolledUser();

    $this->artisan('provisioning:reset-two-factor', ['email' => $user->email])
        ->expectsOutputToContain($user->email)
        ->doesntExpectOutputToContain('JBSWY3DPEHPK3PXP')
        ->assertExitCode(0);

    expect($user->refresh()->app_authentication_secret)->toBeNull();
});

it('refuses an unknown email on the console', function (): void {
    $this->artisan('provisioning:reset-two-factor', ['email' => 'nobody@example.test'])
        ->assertExitCode(1);
});

it('says so and changes nothing when the account is not enrolled', function (): void {
    Event::fake([TwoFactorReset::class]);
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123']);

    $this->artisan('provisioning:reset-two-factor', ['email' => $user->email])
        ->expectsOutputToContain(__('provisioning::provisioning.two_factor.console_not_enrolled', ['email' => $user->email]))
        ->assertExitCode(0);

    Event::assertNotDispatched(TwoFactorReset::class);
});
