<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Contracts\HandlesPasswordChange;
use Rolland\Provisioning\Contracts\ProvidesPasswordRules;
use Rolland\Provisioning\Defaults\LaravelPasswordRules;
use Rolland\Provisioning\Defaults\NeverSkipEnforcement;
use Rolland\Provisioning\Defaults\NoopPasswordChange;
use Rolland\Provisioning\ProvisioningServiceProvider;

/*
 * The provider: config merged, broker registered (constitution III / research R2),
 * hooks bound with safe defaults (R3), everything publishable under four tags.
 */

it('merges the package configuration', function (): void {
    expect(config('provisioning.broker.table'))->toBe('provisioning_tokens')
        ->and(config('provisioning.columns.force_password_change'))->toBe('force_password_change');
});

it('registers the invite broker on its own token table when the application has none', function (): void {
    expect(config('auth.passwords.provisioning'))->toMatchArray([
        'provider' => 'users',
        'table' => 'provisioning_tokens',
        'expire' => 10080,
        'throttle' => 60,
    ]);

    expect(Password::broker('provisioning'))->not->toBeNull();
});

it('leaves a broker the application already defined untouched', function (): void {
    config(['auth.passwords.provisioning' => ['provider' => 'users', 'table' => 'host_tokens', 'expire' => 60, 'throttle' => 1]]);

    (new ProvisioningServiceProvider(app()))->boot();

    expect(config('auth.passwords.provisioning.table'))->toBe('host_tokens');
});

it('binds the three host hooks to their safe defaults', function (): void {
    expect(app(HandlesPasswordChange::class))->toBeInstanceOf(NoopPasswordChange::class)
        ->and(app(DecidesEnforcementSkip::class))->toBeInstanceOf(NeverSkipEnforcement::class)
        ->and(app(ProvidesPasswordRules::class))->toBeInstanceOf(LaravelPasswordRules::class);
});

it('binds a hook to the class the application configured', function (): void {
    $custom = new class implements HandlesPasswordChange
    {
        public function passwordChanged(Authenticatable $user, string $newPassword): void {}
    };

    config(['provisioning.hooks.password_changed' => $custom::class]);

    expect(app(HandlesPasswordChange::class))->toBeInstanceOf($custom::class);
});

it('publishes configuration, migration stubs, translations and views under their tags', function (string $tag): void {
    expect(ServiceProvider::pathsToPublish(ProvisioningServiceProvider::class, $tag))->not->toBeEmpty();
})->with(['provisioning-config', 'provisioning-migrations', 'provisioning-translations', 'provisioning-views']);
