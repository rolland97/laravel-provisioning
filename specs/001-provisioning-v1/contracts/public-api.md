# Contract: public API of laravel-provisioning v1

Namespace `Rolland\Provisioning`. Everything not listed is internal.

## Actions (core, container-resolved, each `handle()`)

```php
Actions\SendInvite::handle(Authenticatable&CanResetPassword $user, ?Authenticatable $causer, bool $resend = false): void
Actions\AcceptInvite::handle(string $email, #[SensitiveParameter] string $token, #[SensitiveParameter] string $password): bool   // false = invalid/expired/used
Actions\CompleteProvisioning::handle(Authenticatable $user, #[SensitiveParameter] string $password): void   // called by AcceptInvite
Actions\ArmForcedPasswordChange::handle(Authenticatable $user): void
Actions\CompleteForcedPasswordChange::handle(Authenticatable $user, #[SensitiveParameter] string $newPassword): void   // ValidationException when same as current
Actions\ResetTwoFactor::handle(Authenticatable $user, ?Authenticatable $causer): void
Support\InviteUrl::for(Authenticatable&CanResetPassword $user, #[SensitiveParameter] string $token): string   // signed, expires with the token
```

## Contracts (host hooks; defaults in brackets)

```php
Contracts\HandlesPasswordChange::passwordChanged(Authenticatable $user, #[SensitiveParameter] string $newPassword): void   // [no-op]
Contracts\DecidesEnforcementSkip::shouldSkip(Request $request, Authenticatable $user): bool                            // [false]
Contracts\DecidesEnforcementSkip::blocksCompletion(Authenticatable $user): bool                                        // [false]
Contracts\ProvidesPasswordRules::rules(): array                                                                        // [[Password::defaults()]]
Contracts\ProvidesPasswordRules::requirementsView(): ?string                                                           // [null] bridge only
```

## Middleware

`Http\Middleware\EnforcePasswordChange` (core: default guard, `provisioning.enforcement.except`
route patterns, redirect to `provisioning.enforcement.redirect_route`); the bridge's panel variant
is registered by the plugin.

## Console

`provisioning:reset-two-factor {email}` — calls `ResetTwoFactor` with a null causer; exit 1 when no
such user; reports when the account is not enrolled and changes nothing.

## Events

`Events\InviteSent`, `InviteResent`, `InviteAccepted`, `ForcedPasswordChangeCompleted`,
`TwoFactorReset` — public readonly scalar properties only (see data-model.md).

## Filament bridge

```php
$panel->plugin(
    ProvisioningPlugin::make()
        ->forcePasswordChange()        // page + persistent trap middleware for this panel
        ->acceptsInvites()             // this panel's login is where an accepted invite returns
);
Filament\Actions\SendInviteAction::make()          // create/edit pages; ->resend()
Filament\Actions\ResetTwoFactorAction::make()      // visible when enrolled
```

## Config (`config/provisioning.php`)

`columns.*` (6), `broker.{name,provider,table,expire,throttle}`, `invite.{path,mail_view}`,
`enforcement.{except,redirect_route}`, `hooks.{password_changed,enforcement_skip,password_rules}`,
`activity_log` (bool), `record_logins` (bool).

## Publishing tags

`provisioning-config`, `provisioning-migrations` (stubs), `provisioning-translations`,
`provisioning-views` (mail + accept page).
