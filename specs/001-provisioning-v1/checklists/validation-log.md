# Validation log: laravel-provisioning v1

## Gates

- Implementation gate (2026-10-20): artifacts READY, TDD UPSTREAM, no blocking findings;
  requirements checklist 16/16. AGENTS.md loaded before planning (sha `3bfb83a6`).

## Seen red before green (constitution I)

| Guard | How it was made to fail |
|---|---|
| T004 plugin seam (page + persistent middleware on one panel) | plugin removed from the `admin` panel |
| Events carry no secrets | a model-typed `user` and a `token` property added to `InviteSent` |
| Host-defined broker left untouched | the "already defined" check removed |
| Queued invite encrypted | `ShouldBeEncrypted` removed |
| Trap exemptions + skip hook | exemption loop and skip check removed |
| Column renames (SC-005) | `Columns` returning the default names |
| Activity-log and sign-in off switches | each config check removed |
| Remember token rotates on a forced change | written first (security review), red before the fix |
| Admin actions deny by default | written first (security review), red before `authorize()` |
| Distribution archive | red until README existed |

All other cases were red because their classes did not exist yet.

## T034 — full local gate (2026-10-20, HEAD `4c27f6a`)

- `pint --test` passed; `phpstan analyse` (level 8) no errors.
- `pest --testsuite=core,bridge`: **93 passed** (259 assertions).
- `pest --testsuite=browser` (Playwright 1.64, Chromium v1248): **7 passed**; three consecutive
  clean runs earlier. One unexplained failure of "sends an armed user to the change page"
  inside a suite run right after a test edit (6.8 s, message not captured) did not recur in
  any later run — watch for it in CI.
- Core with `filament/filament` and `spatie/laravel-activitylog` removed (a `git archive`
  copy): **61 passed, 7 skipped** (the 6 activity-log cases and the archive check, which needs
  a git checkout).

## Security review findings (automated, during implementation)

1. Remember token not rotated on a forced change — **fixed** (`d532f2f`). Ending other live
   sessions stays host policy (`HandlesPasswordChange`); documented in the README.
2. Admin actions without their own authorization — **fixed**: deny by default against
   configurable Gate abilities (`ce717f0`).

## Changes from the plan

- Pest ^4 → ^5 (+ browser plugin ^5, PHPUnit 13), Playwright 1.64 — see plan, Technical Context.
- `invite.route` config added: with the plugin, the accept page lives under the accepting
  panel (`filament.{panel}.provisioning.accept`), not at the application root.
- `provisioning.abilities` config added (security review 2).
