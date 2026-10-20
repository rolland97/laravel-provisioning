<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Rolland\Provisioning\ProvisioningServiceProvider;
use Rolland\Provisioning\Tests\Fixtures\User;

/**
 * ⚠️ Nothing in this class may reference Filament, directly or through a use statement.
 *
 * The `core` suite runs in CI with `filament/filament` UNINSTALLED (AGENTS.md R-001,
 * R-022), and this base class is loaded by every suite.
 */
abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ProvisioningServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * The users table is the fixture's; the provisioning columns and the token table
     * come from the package's own stubs, so every test also proves the stubs.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }
}
