# Research: laravel-provisioning v1

Source of behaviour: `ew-global-sdn-bhd/procument` `dev6` `9429aa53` (2026-10-20). Framework
facts checked against `laravel/framework` 13.x in that application's `vendor/`.

## R1 — Encrypting the queued invite (FR-005)

`Illuminate\Notifications\SendQueuedNotifications` sets `$shouldBeEncrypted = $notification
instanceof ShouldBeEncrypted` (`SendQueuedNotifications.php:111`), and `Queue::createPayload`
encrypts the command when `jobShouldBeEncrypted()` (`Queue.php:198, 295-301`). **Decision**: the
invite notification implements `ShouldQueue` and `ShouldBeEncrypted`; a core test asserts the
pushed payload does not contain the token. **Alternative**: store a token id and resolve it in
the job — more moving parts for the same result. The source application does not encrypt today
(its token is a public property of a queued notification); adoption gains this.

## R2 — The invite broker and its table (FR-002)

Source: `config/auth.php:120-125` — broker `provisioning`, provider `users`, table
`provisioning_tokens`, expire 10080, throttle 60; split from `password_reset_tokens` in 089 after
the two brokers evicted each other's tokens. **Decision**: the package registers the broker at
boot from `config('provisioning.broker')` (`name`, `provider`, `table`, `expire`, `throttle`),
merging into `auth.passwords` only when the host has not defined that name — so a host that
forgets nothing still works, and a host that defines it keeps control. The token-table stub
mirrors the 089 migration. **Alternative**: README tells the host to add it — fails at invite
time in production when forgotten.

## R3 — Host hooks (FR-016) as contracts with safe defaults

| Hook | Contract | Default | Source behaviour (kept in the consumer) |
|---|---|---|---|
| password changed | `Contracts\HandlesPasswordChange::passwordChanged(Authenticatable $user, string $newPassword): void` | no-op | `LogOutOtherSessions` (091) |
| skip enforcement | `Contracts\DecidesEnforcementSkip::shouldSkip(Request $request, Authenticatable $user): bool` and `blocksCompletion(Authenticatable $user): bool` | `false` / `false` | `PermittedImpersonation::isActive()` / `isImpersonating()` (104) |
| password rules | `Contracts\ProvidesPasswordRules::rules(): array` and `requirementsView(): ?string` (bridge) | `[Password::defaults()]`, `null` | `Password::defaults()` + `PasswordRequirements` meter view (092) |

Bound in the container by class name from config (`provisioning.hooks.*`). **Alternative**:
events for all three — a skip rule must return a decision, which an event cannot.

## R4 — The trap middleware (FR-008, FR-010)

Source: `EnforcePasswordChange.php` reads `Filament::auth()->user()`, passes on
`*livewire.update`, `filament.*.auth.logout`, `filament.*.pages.force-password-change`,
`filament.*.auth.multi-factor-authentication.*`, else redirects to the panel's change page.
**Decision**: core middleware `EnforcePasswordChange` uses the default guard and a configurable
`except` list of route-name patterns plus a configurable redirect route; the bridge plugin adds a
panel-aware variant with the four Filament patterns built in and the panel's own change-page URL,
registered by the plugin as a **persistent** auth middleware (source: `isPersistent: true`, so a
flag set mid-session traps immediately).

## R5 — The accept-invite page (FR-003, FR-004)

Source: `Route::livewire('/accept-invite/{token}')` named `accept-provisioning-invite`, outside
the panels, with `ValidateSignature::relative()`; component `AcceptProvisioningInvite` resets via
the broker and redirects to the `app` panel's login. **Decision**: core provides
`Actions\AcceptInvite` (broker reset + `CompleteProvisioning`) and the signed-URL builder; the
bridge registers the route (path from config, name `provisioning.accept`) and a Livewire page
using Filament's simple layout, returning to the configured panel's login. A non-Filament host
posts to its own form and calls the action. The consumer keeps its URL by configuring the path
and, during adoption, a route alias for links already mailed (7-day window).

## R6 — Two-factor columns (FR-012, FR-014)

Filament 5's `InteractsWithAppAuthentication` uses `app_authentication_secret` /
`app_authentication_recovery_codes` (`encrypted:array`). Laravel Fortify uses `two_factor_*`.
**Decision**: `provisioning.columns.two_factor_secret` and `.two_factor_recovery_codes`, defaults
matching Filament's; reset nulls the configured columns via `forceFill`.

## R7 — Events and the optional activity-log listener (FR-017, FR-018, R-018)

Events (`Events\InviteSent`, `InviteResent`, `InviteAccepted`, `ForcedPasswordChangeCompleted`,
`TwoFactorReset`) carry `int|string $userId`, `int|string|null $causerId`, and for the reset
`string $via` (`admin`|`console`). **Decision**: `Listeners\RecordProvisioningActivity` writes
`activity()->performedOn($user)->causedBy($causer)->event($name)->log($name)` with the five source
names, registered only when `class_exists(\Spatie\Activitylog\ActivityLogger::class)` and
`config('provisioning.activity_log')` is true (default true when installed).

## R8 — Sign-in record (FR-013)

Source listener: `$user->increment('login_count', 1, ['last_login_at' => now()])` on `Login`.
**Decision**: same single update with configured column names; registered by the provider only
when `provisioning.record_logins` is true (default true).

## R9 — Suites and CI (constitution V, R-022…R-026)

Copy `rolland97/laravel-tree`'s shape: `tests/Core`, `tests/Bridge`, `tests/Browser`; Orchestra
Testbench 11 with a served Filament panel for bridge/browser; CI matrix PHP 8.3/8.4/8.5 ×
prefer-lowest/prefer-stable on core+bridge, a `core-without-filament` job (`composer remove
filament/filament --dev` then the core suite), and a browser job with Playwright. Actions pinned
by SHA as in that repository.

## R10 — Namespace and name

`rolland97/laravel-provisioning`, namespace `Rolland\Provisioning`, provider
`ProvisioningServiceProvider`, bridge `Rolland\Provisioning\Filament\ProvisioningPlugin`, config
`provisioning.php`, translations `provisioning::` (`en`, `ms`). Matches `Rolland\Tree`.

## R4 addendum — spike result (T004, 2026-10-20)

Proven in `tests/Bridge/SpikeTest.php` against Filament 5 in Testbench: a `Filament\Contracts\Plugin`
whose `register(Panel $panel)` calls `$panel->pages([...])->authMiddleware([...], isPersistent: true)`
(a) serves its page through the middleware on that panel (`/admin/spike`, header present);
(b) adds neither page nor middleware to a panel without it (`/plain/spike` 404, `/plain` no header);
(c) puts the middleware in `Livewire::getPersistentMiddleware()`, so Livewire round trips run it.
Seen red: with the plugin removed from the `admin` panel, (a) and (c) fail, and pass again when restored.
