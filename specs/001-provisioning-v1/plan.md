# Implementation Plan: laravel-provisioning v1

**Branch**: `001-provisioning-v1` | **Date**: 2026-10-20 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-provisioning-v1/spec.md`

## Summary

Extract the procurement application's provisioning flow into `rolland97/laravel-provisioning`:
core actions (send/accept invite, arm and complete the forced change, reset two-factor), a
broker the package registers on its own token table, an encrypted queued invite, a trap
middleware, a console escape hatch, five scalar-only domain events, an optional activity-log
listener keeping the source's event names, and a sign-in recorder — all plain Laravel. A
Filament 5 panel plugin adds the accept-invite page, the change page with a persistent trap, and
admin actions. Three host hooks (password changed, skip enforcement, password rules) replace the
source's session, impersonation and password-meter couplings. Design in [research.md](research.md);
API in [contracts/public-api.md](contracts/public-api.md); schema in [data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP ^8.4 (developed on 8.5) — ⚠️ amended from ^8.3 after the first CI run: Pest 5 requires 8.4 (constitution 1.1.0)

**Primary Dependencies**: `illuminate/{support,auth,notifications,queue,database,console}` ^13; dev: `filament/filament` ^5, `orchestra/testbench` ^11, `pestphp/pest` ^5 + `pestphp/pest-plugin-browser` ^5 (PHPUnit 13), Playwright 1.64 (npm), `larastan/larastan` ^3, `laravel/pint`, `spatie/laravel-activitylog` ^5 — ⚠️ amended during T029 from Pest ^4: Pest 4's browser plugin hangs on Playwright after 1.61, and Pest 5 / Playwright 1.64 are the consumer's versions, so the shared browser cache keeps one Chromium; suggest: `filament/filament`, `spatie/laravel-activitylog`

**Storage**: host database — four user columns (stub, guarded), one token table (stub)

**Testing**: Pest — `core` (Filament uninstalled in CI), `bridge` (Testbench panel with and without the plugin), `browser` (Playwright: accept page, change page, light/dark)

**Target Platform**: any Laravel 13 application; Filament 5 panels for the bridge

**Project Type**: Composer library (Laravel package) with an optional Filament bridge

**Performance Goals**: sign-in record is one UPDATE; the trap adds no query when the flag is off beyond the authenticated user already loaded

**Constraints**: no secret in events, logs, audit or clear queue payload; no auto-migration; installs lock nobody out; no Filament in `require`

**Scale/Scope**: ~9 source classes ported + 3 contracts, 5 events, plugin, 2 pages, 2 admin actions, 1 command; ~10 ported test files plus new ones for hooks, encryption, column renames and the no-Filament core

## Constitution Check

*Gate before Phase 0; re-checked after Phase 1 — unchanged.*

| Principle | Status | How |
|---|---|---|
| I. Test-first | ✅ | each story starts with its ported or new failing test; consumer keeps its copies (adoption proof) |
| II. No secret leaves | ✅ | encrypted queued invite (R1); separate token table (R2); scalar events (R7); `#[SensitiveParameter]` on every secret parameter |
| III. Host owns words, storage, audit, policy | ✅ | stubs only; 6 columns + table configurable; `en`/`ms` publishable; optional activity listener; three hook contracts (R3) |
| IV. Never lock a person out | ✅ | stub default `false`; trap exemptions + skip hook (R4); one reset path + console command; queued invite |
| V. Core is Laravel | ✅ | `src/` free of Filament; `core-without-filament` CI job; plugin per panel |
| VI. Ship runtime only | ✅ | Filament components only in views (no bare utilities); `.gitattributes` export-ignore before tagging |

AGENTS.md (`3bfb83a6`) loaded by the `before_plan` gate; rules R-022…R-027 are re-cited when
Phase 2 creates `phpunit.xml.dist`, `phpstan.neon.dist`, `pint.json`, the workflow,
`composer.json` and `.gitattributes`.

No violations.

## Project Structure

### Documentation (this feature)

```text
specs/001-provisioning-v1/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
├── contracts/public-api.md
├── checklists/requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
composer.json  phpunit.xml.dist  phpstan.neon.dist  pint.json  .gitattributes  README.md  LICENSE
config/provisioning.php
database/migrations/{add_provisioning_columns_to_users_table,create_provisioning_tokens_table}.php.stub
lang/{en,ms}/provisioning.php
resources/views/{mail/invite,filament/pages/force-password-change,filament/pages/accept-invite}.blade.php
src/
├── ProvisioningServiceProvider.php
├── Actions/{SendInvite,AcceptInvite,CompleteProvisioning,ArmForcedPasswordChange,CompleteForcedPasswordChange,ResetTwoFactor}.php
├── Contracts/{HandlesPasswordChange,DecidesEnforcementSkip,ProvidesPasswordRules}.php
├── Defaults/{NoopPasswordChange,NeverSkipEnforcement,LaravelPasswordRules}.php
├── Events/{InviteSent,InviteResent,InviteAccepted,ForcedPasswordChangeCompleted,TwoFactorReset}.php
├── Http/Middleware/EnforcePasswordChange.php
├── Listeners/{RecordSuccessfulLogin,RecordProvisioningActivity}.php
├── Notifications/ProvisioningInvite.php
├── Console/ResetTwoFactorCommand.php
├── Support/{Columns,InviteUrl}.php
└── Filament/
    ├── ProvisioningPlugin.php
    ├── Http/Middleware/EnforcePanelPasswordChange.php
    ├── Pages/{ForcePasswordChange,AcceptInvite}.php
    └── Actions/{SendInviteAction,ResetTwoFactorAction}.php
tests/{Core,Bridge,Browser}/  tests/Fixtures/  (+ Pest.php, TestCase)
.github/workflows/tests.yml
```

**Structure Decision**: mirror `rolland97/laravel-tree` (provider at `src/`, bridge at
`src/Filament/`, three suites, Testbench-served panel).

## Complexity Tracking

None.
