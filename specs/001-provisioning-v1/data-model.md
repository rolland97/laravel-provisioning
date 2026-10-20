# Data model: laravel-provisioning v1

## Columns on the host's users table (stub `add_provisioning_columns_to_users_table.php.stub`)

| Config key (`provisioning.columns.*`) | Default name | Type | Default | Notes |
|---|---|---|---|---|
| `force_password_change` | `force_password_change` | boolean | **false** | constitution IV; the source app uses `true` — it configures nothing, it already has the column |
| `password_changed_at` | `password_changed_at` | timestamp, nullable | null | set on forced-change completion |
| `last_login_at` | `last_login_at` | timestamp, nullable | null | sign-in record; re-send offered while null |
| `login_count` | `login_count` | unsigned int | 0 | sign-in record |
| `two_factor_secret` | `app_authentication_secret` | text, nullable | null | Filament 5's name; **not** added by the stub (owned by the 2FA provider) |
| `two_factor_recovery_codes` | `app_authentication_recovery_codes` | text, nullable | null | same |

The stub adds only the first four, each guarded by `Schema::hasColumn` so a host that already
has them (the consumer) publishes nothing it needs.

## Invite token table (stub `create_provisioning_tokens_table.php.stub`)

`provisioning.broker.table` (default `provisioning_tokens`): `email` (primary, string),
`token` (string, hashed), `created_at` (timestamp, nullable) — the framework's token-repository
shape, as the source 089 migration.

## State

- Account: `invited` (unusable password, no sign-in) → `active` (accepted) ; any → `armed`
  (password set by someone else) → `active` (forced change completed).
- Token: issued → consumed | expired | superseded (new invite).

## Events (identifiers and plain values only)

| Event | Fields | Activity name |
|---|---|---|
| `InviteSent` | `userId`, `causerId` | `provisioning.sent` |
| `InviteResent` | `userId`, `causerId` | `provisioning.resent` |
| `InviteAccepted` | `userId` | `provisioning.completed` (causer = the user) |
| `ForcedPasswordChangeCompleted` | `userId` | `password.force_change_completed` (causer = the user) |
| `TwoFactorReset` | `userId`, `causerId`, `via` (`admin`/`console`) | `2fa.reset` (property `via`) |
