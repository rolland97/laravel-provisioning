<?php

declare(strict_types=1);

namespace Rolland\Provisioning;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Rolland\Provisioning\Console\ResetTwoFactorCommand;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Contracts\HandlesPasswordChange;
use Rolland\Provisioning\Contracts\ProvidesPasswordRules;
use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Events\InviteAccepted;
use Rolland\Provisioning\Events\InviteResent;
use Rolland\Provisioning\Events\InviteSent;
use Rolland\Provisioning\Events\TwoFactorReset;
use Rolland\Provisioning\Listeners\ClearForcedChangeOnPasswordReset;
use Rolland\Provisioning\Listeners\RecordProvisioningActivity;
use Rolland\Provisioning\Listeners\RecordSuccessfulLogin;

final class ProvisioningServiceProvider extends ServiceProvider
{
    /** @var array<class-string, string> contract => config key under `provisioning.hooks` */
    private const array HOOKS = [
        HandlesPasswordChange::class => 'password_changed',
        DecidesEnforcementSkip::class => 'enforcement_skip',
        ProvidesPasswordRules::class => 'password_rules',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/provisioning.php', 'provisioning');

        // Resolved on every request, not once: a host (or a test) that changes the
        // configured class at runtime is honoured.
        foreach (self::HOOKS as $contract => $key) {
            $this->app->bind($contract, fn ($app) => $app->make((string) config("provisioning.hooks.{$key}")));
        }
    }

    public function boot(): void
    {
        $this->registerBroker();
        $this->registerListeners();

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'provisioning');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'provisioning');

        if ($this->app->runningInConsole()) {
            $this->commands([ResetTwoFactorCommand::class]);

            $this->publishes([__DIR__.'/../config/provisioning.php' => config_path('provisioning.php')], 'provisioning-config');
            $this->publishes([
                __DIR__.'/../database/migrations/add_provisioning_columns_to_users_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_provisioning_columns_to_users_table.php'),
                __DIR__.'/../database/migrations/create_provisioning_tokens_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_provisioning_tokens_table.php'),
            ], 'provisioning-migrations');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/provisioning')], 'provisioning-translations');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/provisioning')], 'provisioning-views');
        }
    }

    /**
     * The sign-in recorder, the reset that clears the forced change (002), and — only when spatie/laravel-activitylog is installed —
     * the activity writer. Each checks its own config switch when it runs.
     */
    private function registerListeners(): void
    {
        Event::listen(Login::class, RecordSuccessfulLogin::class);
        Event::listen(PasswordReset::class, ClearForcedChangeOnPasswordReset::class);

        // The `activity()` helper, not a class: activitylog moved its logger class
        // between majors (v5: Support\\ActivityLogger), the helper stayed.
        if (function_exists('activity')) {
            foreach ([InviteSent::class, InviteResent::class, InviteAccepted::class, ForcedPasswordChangeCompleted::class, TwoFactorReset::class] as $event) {
                Event::listen($event, RecordProvisioningActivity::class);
            }
        }
    }

    /**
     * The invite broker, unless the application already defined one under that name.
     * A broker the host forgot to add would otherwise fail at invite time, in
     * production (research R2).
     */
    private function registerBroker(): void
    {
        $name = (string) config('provisioning.broker.name', 'provisioning');

        if (config("auth.passwords.{$name}") !== null) {
            return;
        }

        config(["auth.passwords.{$name}" => [
            'provider' => config('provisioning.broker.provider', 'users'),
            'table' => config('provisioning.broker.table', 'provisioning_tokens'),
            'expire' => (int) config('provisioning.broker.expire', 10080),
            'throttle' => (int) config('provisioning.broker.throttle', 60),
        ]]);
    }
}
