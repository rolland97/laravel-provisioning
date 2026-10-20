<!--
SYNC IMPACT REPORT
Version change: (template) → 1.0.0 — first ratification.
Principles: I Test-First; II No Secret Leaves; III The Host Owns Words, Storage, Audit and
Policy; IV Never Lock a Person Out; V Core Is Laravel, Filament Is an Optional Bridge;
VI Ship Runtime Only.
Added sections: Platform and Toolchain Constraints; Development Workflow and Quality Gates;
Governance.
Templates: plan-template.md Constitution Check reads this file at plan time — no edit needed.
spec-template.md and tasks-template.md carry no principle names — no edit needed.
Follow-up: AGENTS.md (the operational rules MemoryLint loads before planning) does not exist
yet and is owed before /speckit-plan.
-->

# laravel-provisioning Constitution

Admin-created accounts for Laravel: an invite instead of a password, a user who sets their
own, a forced change when someone else set it, and a two-factor reset — each audited by the
host. Extracted from a production procurement application (`ew-global-sdn-bhd/procument`),
where every behaviour here already runs.

## Core Principles

### I. Test-First, and a Guard That Has Never Failed Is Not a Guard

Every behaviour change MUST land with the test that would have caught its absence, and that
test MUST be written and observed failing before the implementation exists. A test whose red
has never been seen MUST be treated as unverified; where a guard cannot be made to fail, that
MUST be investigated, not accepted.

The suite runs in random order and fails on warnings, risky tests and stray output. Tests
MUST NOT depend on execution order and MUST NOT print.

The tests that come from the source application MUST port with their intent intact, and the
source application MUST keep its own copies unchanged as adoption proof.

**Rationale**: a package that runs inside other people's sign-in flow cannot be debugged by
its author when it breaks, and a provisioning defect fails closed (a person locked out) or
open (an account anyone can take). Both are invisible to a green suite that never saw red.

### II. No Secret Leaves

No password is ever transmitted or stored in clear: an account is created **without** one,
and the person sets their own through a **single-use, expiring** invite token; issuing a new
invite MUST invalidate the previous one.

Invite tokens MUST be kept apart from password-reset tokens, so that neither flow can evict
or consume the other's.

Secret material — passwords, tokens, two-factor secrets, recovery codes — MUST NOT reach an
event payload, a log line, a notification or an audit entry. Events MUST carry **ids and
scalars only**, never a model a listener could serialise. Parameters carrying secrets MUST be
marked `#[SensitiveParameter]`.

**Rationale**: in the source application the invite and reset brokers shared one table and
evicted each other's tokens until slice 089 split them; and an event carrying the user model
is one naive listener away from writing a two-factor secret into an audit log.

### III. The Host Owns Words, Storage, Audit and Policy

This package MUST NOT run a migration of its own, MUST NOT hard-code a column, a table, a
panel, a route prefix or a language, and MUST NOT depend on an activity log.

- Schema ships as **stubs** the host publishes. Column names — including the two-factor
  secret and recovery-code columns, which collide with other packages' defaults — and the
  token table are **configuration**.
- Copy ships as publishable translations (English and Malay) the host may override.
- Audit is the host's: the package fires domain events; an **optional** listener writing
  `spatie/laravel-activitylog` entries is registered only when that package is installed, and
  keeps the source application's event names.
- Policy is the host's, through explicit hooks: what happens to other sessions when a
  password changes; when enforcement is skipped (the source application skips it for a
  permitted impersonation); which password rules apply and how they are shown.

**Rationale**: every one of these is a decision the host has already made differently. The
re-audit of 2026-10-20 found three such decisions grown into the source code since August —
ending other sessions, an impersonation bypass, a password meter. Hard-coding any of them
would make the package unusable to the next host.

### IV. Never Lock a Person Out

Installing the package MUST NOT force anyone to change a password: the stub defaults the
forced-change flag to **false**, and arming it is an explicit act. A password the person
chose MUST NOT arm it; a password someone else set MUST.

While the forced change is armed, the person MUST still be able to reach the change page,
sign out, finish two-factor set-up and complete the Livewire round trips those pages need;
anything the host's skip hook allows MUST pass. A two-factor reset MUST have one code path
serving both an administrator action and a **console escape hatch** for the last
administrator locked out.

Account creation MUST succeed when mail fails: the invite is a **queued** notification.

**Rationale**: the failure modes of this feature are a whole user base locked out on deploy
(a `true` default on an existing users table) and a person trapped on a page they cannot
leave. Both are worse than the defect they would prevent.

### V. Core Is Laravel; Filament Is an Optional Bridge

`src/` MUST be plain Laravel and MUST NOT require `filament/filament`: the actions, broker,
middleware, notification, events and console command work from any Laravel application. The
Filament bridge (a panel plugin, the forced-change page, the invite-accept page, admin
actions) lives under `src/Filament/`, with `filament/filament` in `require-dev` and `suggest`
only and its provider registered conditionally. CI MUST prove this with a core suite run with
`filament/filament` **uninstalled**, and the bridge suite MUST cover a panel with the plugin
and one without.

**Rationale**: the reusable value is the flow and its guarantees, not the screens.

### VI. Ship Runtime Only

A host MUST be able to install the package and use it without a bundler, a theme edit or a
`@source` glob into `vendor/`: views use Filament's own components or package-owned compiled
assets. Specifications, agent configuration, tests and build sources MUST be excluded from the
distribution archive **before** the first release tag.

**Rationale**: a Filament panel compiles only the utilities its own CSS references, so a blade
in `vendor/` with bare utility classes renders unstyled and silently; and a published tag
cannot be retracted.

## Platform and Toolchain Constraints

**Platform**: PHP 8.3+, `illuminate/*` ^13 (the one consumer is on Laravel `^13.17`; support
starts narrow and widens when a host needs it — widening is non-breaking, narrowing is not).
The Filament bridge targets v5.

**Static analysis**: PHPStan over `src` and `config` at a level that MUST NOT be lowered, and no
new baseline entries to silence an error. **Formatting**: Pint. **Supply chain**: CI actions
pinned to a full commit SHA with the version in a comment; every workflow declares a
least-privilege `permissions:` block. **Comments**: explain why, only where surprising.

## Development Workflow and Quality Gates

- Specification complete and ambiguities resolved before planning; planning before tasks;
  tasks ordered before implementation.
- **A quoted constraint is a claim**: anything an inherited document says is forbidden,
  fixed or handled MUST be verified against the artifact before it is used as a reason not
  to do work — the PKG-02 inventory was already wrong in twelve places two months after it
  was written.
- **Before completion**: core suite (Filament uninstalled), bridge suite, static analysis and
  formatting all pass; anything visual is checked in a real browser in light and dark.
- **Release**: not tagged until the source application has been built against it unpublished
  and its own copies of the ported tests pass unchanged.
- **Commits**: Conventional Commits, lowercase descriptive subjects; no commit, push, tag or
  release unless asked. **Reporting**: outcomes as they are; work not run is not verified.

## Governance

This constitution supersedes other practices in this repository; where a practice conflicts
with a principle, the practice is the bug. `AGENTS.md` is its operational expression (the
rules MemoryLint loads before planning) and MUST NOT contradict it.

**Amendments** are documented changes to this file stating the principle affected, the reason,
what is traded away if it loosens, and the migration for anything already built. **Versioning**:
MAJOR — a principle removed or redefined to permit what it forbade; MINOR — a principle or
section added or materially expanded; PATCH — wording only. **Compliance**: every plan carries a
Constitution Check against this file, before research and after design.

**Version**: 1.0.0 | **Ratified**: 2026-10-20 | **Last Amended**: 2026-10-20
