# laravel-provisioning

Admin-created accounts for Laravel 13, done safely:

- **An invite instead of a password.** No password is ever mailed. The person gets a
  single-use, expiring link and chooses their own.
- **A forced change when someone else set the password.** If an administrator sets a
  password, the person must replace it at next sign-in, and is never trapped somewhere
  they cannot leave. A completed password reset counts: someone who chooses a password
  through a reset link is not asked to choose one again.
- **A two-factor reset**, from an admin button or, when the last administrator is locked
  out, from the console.
- **Your audit trail.** Every step fires an event with identifiers only; an optional
  listener writes them to `spatie/laravel-activitylog`.

The core is plain Laravel. An optional **Filament v5 panel plugin** adds the pages, the
trap and the admin buttons.

Extracted from a production procurement application, where all of this already runs.

## Requirements

PHP 8.4+, Laravel 13. Filament 5 for the plugin (optional).

## Install

```bash
composer require rolland97/laravel-provisioning
php artisan vendor:publish --tag=provisioning-migrations
php artisan migrate
php artisan vendor:publish --tag=provisioning-config   # optional
```

The migrations add four columns to your users table and create the invite-token table.

- `force_password_change` **defaults to false**, so installing locks nobody out.
- Columns that already exist are skipped.

Your user model needs `Notifiable` and `CanResetPassword`. Laravel's default `User` has
both.

## With Filament

```php
use Rolland\Provisioning\Filament\ProvisioningPlugin;

$panel->plugin(
    ProvisioningPlugin::make()
        ->forcePasswordChange()   // the change page + the trap, on this panel
        ->acceptsInvites()        // invite links open under this panel and return to its sign-in
);
```

**The change page** is a card page, like signing in: logo, the form, and Filament's own
user menu to sign out. No sidebar or top bar — the person can go nowhere until they finish.

**The trap.** It is a persistent auth middleware, so a flag set mid-session traps the
person on their next request. While armed, these stay reachable:

- the change page;
- sign-out;
- two-factor set-up;
- Livewire's update requests;
- anything your skip rule allows (see Hooks).

**Admin buttons**, e.g. on your user resource's edit page:

```php
use Rolland\Provisioning\Filament\Actions\ResetTwoFactorAction;
use Rolland\Provisioning\Filament\Actions\SendInviteAction;

protected function getHeaderActions(): array
{
    return [SendInviteAction::make(), ResetTwoFactorAction::make()];
}
```

⚠️ **Both buttons are denied by default.** Each one authorizes against a Gate ability,
with the user record as the argument: `sendProvisioningInvite` and `resetTwoFactor`
(configurable under `abilities`). Grant them in your user policy, or with
`Gate::define()`.

**Creating an account without a password.** Store an unusable random password, then
send the invite:

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $this->invite = blank($data['password'] ?? null);
    $data['password'] = $this->invite ? Str::password(64) : $data['password'];

    return $data;
}

protected function afterCreate(): void
{
    if ($this->invite) {
        app(SendInvite::class)->handle($this->record, auth()->user());
    } else {
        app(ArmForcedPasswordChange::class)->handle($this->record);   // someone else chose it
    }
}
```

## Without Filament

Every flow is an action you call:

| Action | Call |
|---|---|
| Send an invite | `app(SendInvite::class)->handle($user, $admin)` |
| Re-send an invite | `app(SendInvite::class)->handle($user, $admin, resend: true)` |
| Accept an invite | `app(AcceptInvite::class)->handle($email, $token, $password)` (returns `bool`) |
| Arm the forced change | `app(ArmForcedPasswordChange::class)->handle($user)` |
| Complete the forced change | `app(CompleteForcedPasswordChange::class)->handle($user, $newPassword)` |
| Reset two-factor | `app(ResetTwoFactor::class)->handle($user, $admin)` |

You also need to:

- Name your accept route `provisioning.accept`, or set `invite.route`. It receives
  `{token}` and a signed `email` query parameter; protect it with
  `ValidateSignature::relative()`.
- Add `Rolland\Provisioning\Http\Middleware\EnforcePasswordChange` after authentication,
  with `enforcement.redirect_route` pointing at your change page.

Console: `php artisan provisioning:reset-two-factor someone@example.com`.

## Hooks

Bind your own class in config `hooks`:

| Contract | Default | Used for |
|---|---|---|
| `HandlesPasswordChange` | does nothing | after a forced change, e.g. end the person's other sessions |
| `DecidesEnforcementSkip` | never skips | let a request past the trap (e.g. a permitted impersonation), refuse completion to such a session, and say where to send it (`null`: the panel's home) |
| `ProvidesPasswordRules` | `Password::defaults()`, no view | rules for both pages, plus an optional view rendered under the password field (it receives `statePath`) |

**Sessions after a forced change.** The package rotates the remember token. Other live
sessions are your policy:

- In a Filament panel, `AuthenticateSession` already signs out sessions that still hold
  the old password hash.
- Elsewhere, end them in your `HandlesPasswordChange`.

## Security notes

- **Invite tokens.** They live in their own table (`provisioning_tokens`), never the
  password-reset table. One shared table lets the two flows evict each other's tokens.
- **The queued invite** is encrypted (`ShouldBeEncrypted`), because its token would
  otherwise appear in clear in the queue payload and on queue dashboards.
- **Events and activity entries** carry identifiers and plain values only, never a
  password, token, two-factor secret or recovery code.

## Events and audit

| Event | Activity name |
|---|---|
| `InviteSent` | `provisioning.sent` |
| `InviteResent` | `provisioning.resent` |
| `InviteAccepted` | `provisioning.completed` |
| `ForcedPasswordChangeCompleted` | `password.force_change_completed` |
| `TwoFactorReset` | `2fa.reset`, with property `via`: `admin` or `console` |

The activity-log writer registers only when `spatie/laravel-activitylog` is installed. Turn
it off with `activity_log => false`. A successful sign-in stamps `last_login_at` and
increments `login_count` (`record_logins`).

**A completed password reset** (Laravel's `PasswordReset` event, which fires only after a
successful reset) clears the forced change and stamps `password_changed_at`; when the change
was armed it also fires `ForcedPasswordChangeCompleted`. Turn it off with
`clear_on_password_reset => false`.

## Configuration

Everything you might rename or change is in `config/provisioning.php`:

- the six column names;
- the token table and its lifetime;
- the invite path, route and mail view;
- the trap's exceptions;
- the hooks and abilities.

The two-factor columns default to Filament 5's names (`app_authentication_secret`,
`app_authentication_recovery_codes`).

Translations ship in English and Malay. Publish them with `--tag=provisioning-translations`.

## Development

```bash
composer install && npm install
composer test:core     # also run in CI with filament/filament removed
composer test:bridge
composer test:browser  # Playwright 1.64
composer analyse && composer test:lint
```

Specification, plan and decisions: `specs/001-provisioning-v1/`. Principles:
`.specify/memory/constitution.md`.

## License

MIT
