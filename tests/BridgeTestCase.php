<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\View;
use Livewire\LivewireServiceProvider;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;
use Rolland\Provisioning\Tests\Fixtures\Panel\AdminPanelProvider;
use Rolland\Provisioning\Tests\Fixtures\Panel\PlainPanelProvider;

/**
 * Two panels, always: `admin` registers the package's plugin and `plain` does not, so
 * every bridge test can show the plugin touches only the panel that asked for it
 * (spec SC-003).
 */
abstract class BridgeTestCase extends TestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            AdminPanelProvider::class,
            PlainPanelProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
        $app['config']->set('auth.providers.users.model', BridgeUser::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('provisioning-tests', __DIR__.'/Fixtures/views');
    }
}
