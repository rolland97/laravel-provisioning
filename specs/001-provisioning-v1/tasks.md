# Tasks: laravel-provisioning v1

**Input**: `specs/001-provisioning-v1/` — spec.md, plan.md, research.md, data-model.md, contracts/public-api.md, quickstart.md

**Tests**: Required (constitution I). Every story's tests are written first and seen failing. Ported tests come from `ew-global-sdn-bhd/procument` `dev6` `9429aa53` (`$SRC` below = `/home/rolland/projects/procurement`); the source keeps its own copies unchanged.

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup — the package skeleton

- [X] T001 `composer.json` (`rolland97/laravel-provisioning`, PHP ^8.3, `illuminate/*` ^13, dev: filament ^5, testbench ^11, pest ^4 + browser plugin, larastan ^3, pint; suggest filament, spatie/laravel-activitylog; PSR-4 `Rolland\Provisioning\` → `src/`, tests `Rolland\Provisioning\Tests\`; provider auto-discovery; scripts as `rolland97/laravel-tree`); `composer install`
- [X] T002 [P] `phpunit.xml.dist` (random order, fail on warning/risky/notice/deprecation/empty, strict output; suites `core`, `bridge`, `browser`), `phpstan.neon.dist` (src + config, level 8), `pint.json`, `.gitignore`, `LICENSE` (MIT) — re-cite AGENTS R-023/R-024/R-026
- [X] T003 [P] `tests/Pest.php`, `tests/TestCase.php` (Testbench; sqlite memory; loads stubs as migrations; registers the provider), `tests/Fixtures/User.php` (Authenticatable + CanResetPassword + Notifiable, the six columns) and its fixture migration
- [X] T004 Spike (constitution "prove fragile seams first"): a bridge test boots a Testbench Filament panel that registers a plugin adding a page and a **persistent** auth middleware, and a second panel without it; assert the middleware runs only on the first panel and survives a Livewire update round trip. Record the mechanism in research.md (R4 addendum)

## Phase 2: Foundational

- [X] T005 `config/provisioning.php` (columns ×6, broker, invite path + mail view, enforcement except/redirect, hooks ×3, `activity_log`, `record_logins`) and `src/Support/Columns.php` (typed accessors)
- [X] T006 [P] Stubs `database/migrations/add_provisioning_columns_to_users_table.php.stub` (four columns, each `hasColumn`-guarded, `force_password_change` default **false**) and `create_provisioning_tokens_table.php.stub` (email PK, token, created_at)
- [X] T007 [P] Contracts `HandlesPasswordChange`, `DecidesEnforcementSkip`, `ProvidesPasswordRules` + defaults `NoopPasswordChange`, `NeverSkipEnforcement`, `LaravelPasswordRules`
- [X] T008 [P] Events `InviteSent`, `InviteResent`, `InviteAccepted`, `ForcedPasswordChangeCompleted`, `TwoFactorReset` — public readonly scalars only
- [X] T009 Core test `tests/Core/ProviderTest.php` first (red): provider registers the broker from config when the host has none and leaves a host-defined broker untouched; binds the three hooks from config; publishes config/stubs/translations/views under the four tags; boots with Filament absent (class check). Then `src/ProvisioningServiceProvider.php` (green)
- [X] T010 Core test `tests/Core/EventsCarryNoSecretsTest.php` (red): reflection over every event — no object-typed property, no property named like password/token/secret/code

---

## Phase 3: US1 — Invite a person instead of choosing their password (P1) 🎯

- [X] T011 [US1] Port `$SRC/tests/Feature/Accounts/ProvisionUserTest.php` → `tests/Core/SendInviteTest.php` (red): token issued in `provisioning_tokens`, notification queued to the user, creation-succeeds-when-mail-fails, resend invalidates the previous token, events `InviteSent`/`InviteResent` with ids
- [X] T012 [P] [US1] Port `$SRC/tests/Feature/Accounts/TokenIndependenceTest.php` → `tests/Core/TokenIndependenceTest.php` (red): an invite token and a password-reset token for the same email coexist and neither consumes the other
- [X] T013 [P] [US1] New `tests/Core/InviteIsEncryptedOnTheQueueTest.php` (red): with a real `database`/`sync`-capturing queue, the pushed payload does not contain the token
- [X] T014 [P] [US1] Port `$SRC/tests/Feature/Accounts/AcceptProvisioningInviteTest.php` (core parts) → `tests/Core/AcceptInviteTest.php` (red): valid token sets the password, does not arm the forced change, rotates remember token, fires `InviteAccepted`; used / expired / superseded / wrong-email tokens refused with nothing changed
- [X] T015 [P] [US1] Port `$SRC/tests/Feature/Accounts/ProvisioningMailRenderTest.php` → `tests/Core/InviteMailTest.php` (red): signed URL to the configured path, expiry text, no token outside the URL, host-overridable view
- [X] T016 [US1] Implement `Notifications/ProvisioningInvite` (ShouldQueue + ShouldBeEncrypted), `Support/InviteUrl`, `Actions/SendInvite`, `Actions/CompleteProvisioning`, `Actions/AcceptInvite`, `resources/views/mail/invite.blade.php`, `lang/en|ms` keys used — T011–T015 green

## Phase 4: US2 — A password someone else set must be changed first (P1)

- [X] T017 [US2] Port `$SRC/tests/Feature/Accounts/ArmForcePasswordChangeTest.php` → `tests/Core/ArmForcedPasswordChangeTest.php` (red): explicit arm action; a person-chosen password never arms
- [X] T018 [P] [US2] Port `$SRC/tests/Feature/Accounts/EnforcePasswordChangeTest.php` (core parts) → `tests/Core/EnforcePasswordChangeTest.php` (red): armed user redirected; configured `except` routes pass; skip hook true passes and leaves the flag; flag off passes
- [X] T019 [P] [US2] New `tests/Core/CompleteForcedPasswordChangeTest.php` (red, from `$SRC` `CompleteForcedPasswordChange` + `Security/PasswordChangeSessionTest.php` intent): same-as-current refused (no change); success clears flag, stamps `password_changed_at`, calls `HandlesPasswordChange` once with the user, fires the event; `blocksCompletion` true refuses
- [X] T020 [US2] Implement `Actions/ArmForcedPasswordChange`, `Actions/CompleteForcedPasswordChange`, `Http/Middleware/EnforcePasswordChange` — T017–T019 green

## Phase 5: US3 — Reset someone's two-factor (P2)

- [ ] T021 [US3] Port `$SRC/tests/Feature/Accounts/ResetTwoFactorTest.php` → `tests/Core/ResetTwoFactorTest.php` (red): configured columns nulled; event `via` admin/console with causer id; command resets by email, exit 1 for an unknown email, reports a not-enrolled account and changes nothing; no secret in event or output
- [ ] T022 [US3] Implement `Actions/ResetTwoFactor`, `Console/ResetTwoFactorCommand` (`provisioning:reset-two-factor {email}`) — T021 green

## Phase 6: US5 — Keep an audit trail the host chooses (P3, before the bridge: core only)

- [ ] T023 [US5] Port `$SRC/tests/Feature/Accounts/RecordSuccessfulLoginTest.php` → `tests/Core/RecordSuccessfulLoginTest.php` (red): one UPDATE (query count), configured columns, disabled by `record_logins=false`
- [ ] T024 [P] [US5] New `tests/Core/ActivityLogListenerTest.php` (red; `spatie/laravel-activitylog` in require-dev): five entries with the exact source names, `via` property, causer; with `activity_log=false` none; core suite still passes when the activity log is absent (class-existence guard)
- [ ] T025 [US5] Implement `Listeners/RecordSuccessfulLogin`, `Listeners/RecordProvisioningActivity` + conditional registration — T023–T024 green

## Phase 7: US4 — Install with or without Filament (P2)

- [ ] T026 [US4] New `tests/Core/ColumnRenamesTest.php` (red): rename all six columns and the token table in config; every core flow passes
- [ ] T027 [P] [US4] Bridge tests (red): `tests/Bridge/PluginTest.php` (panel with plugin: change page route, persistent trap, accept route + page; panel without: none); `tests/Bridge/ForcePasswordChangePageTest.php` (port of the `$SRC` page behaviour: refuses when not armed, refuses when `blocksCompletion`, saves and keeps the session, requirements view from the hook); `tests/Bridge/AcceptInvitePageTest.php` (form, confirmation mismatch, success returns to the configured panel login); `tests/Bridge/AdminActionsTest.php` (send/resend visible only before first sign-in; reset visible only when enrolled)
- [ ] T028 [US4] Implement `Filament/ProvisioningPlugin`, `Filament/Http/Middleware/EnforcePanelPasswordChange`, `Filament/Pages/ForcePasswordChange`, `Filament/Pages/AcceptInvite`, `Filament/Actions/{SendInviteAction,ResetTwoFactorAction}`, their views (Filament components only) and `lang/ms` complete — T026–T027 green
- [ ] T029 [US4] Browser tests `tests/Browser/` (Playwright): accept-invite and change pages render styled, keyboard-usable, in light and dark; trap redirect visible; then run them

## Phase 8: Polish & release readiness

- [ ] T030 [P] `.gitattributes` export-ignore (specs, .specify, .claude, AGENTS.md, CLAUDE.md, tests, workflow, build configs) and a test/archive check (`git archive` lists runtime files only) — re-cite R-027
- [ ] T031 [P] `.github/workflows/tests.yml` (matrix 8.3/8.4/8.5 × lowest/stable on core+bridge; `core-without-filament`; browser job), actions SHA-pinned as `rolland97/laravel-tree`, least-privilege permissions — re-cite R-022/R-025
- [ ] T032 [P] `README.md` (install, publish tags, config, the three hooks, plugin wiring, upgrading an app that already has the columns) and `CLAUDE.md` session orientation
- [ ] T033 Re-cite every `[pending artifact]` rule in `AGENTS.md` against the files Phase 1/8 created; retag `[ratified]`
- [ ] T034 Full local gate: core, core with Filament removed, bridge, browser, phpstan, pint; record in `specs/001-provisioning-v1/checklists/validation-log.md`
- [ ] T035 ⚠️ Outward: create the public GitHub repository `rolland97/laravel-provisioning` (owner-approved 2026-10-20), set default branch `001-provisioning-v1`, push; CI green on all jobs; record run ids
- [ ] T036 `/speckit-superb-critique`; fix Critical and Important
- [ ] T037 Owner review of the public API before any consumer adoption

**Not in this repository**: the adoption slice (`specs/NNN-adopt-laravel-provisioning` in the
consumer) and the first release tag — the constitution allows a tag only after that slice
builds against the unpublished package with its own tests unchanged.

---

## Dependencies & execution order

Phase 1 → T004 spike must pass before Phase 7 → Phase 2 → US1 → US2 → US3 → US5 → US4 →
Phase 8. Within a story: tests (red) → implementation (green). [P] tasks touch different files.
