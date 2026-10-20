# AGENTS.md

Core rules for agents working in `rolland97/laravel-provisioning`.

Every rule cites its source. If a rule and its source disagree, **the source wins and this
file is the bug**. Loaded as a mandatory gate before `/speckit-plan`
(`.specify/extensions.yml` → `hooks.before_plan`).

Tags: **[ratified]** — the source exists in this repository now. **[inherited]** — the source
is in the consumer application (`ew-global-sdn-bhd/procument`, `dev6`), path given.
**[pending artifact]** — settled, but the enforcing file does not exist yet; ⚠️ re-cite when
the plan creates it.

---

## The package boundary

- **R-001** — `src/` MUST NOT require `filament/filament` and MUST load without it; the bridge
  lives in `src/Filament/`, Filament in `require-dev` + `suggest`, provider registered
  conditionally. *Constitution V; spec FR-019.* **[ratified]**
- **R-002** — No auto-running migration. Schema ships as stubs the host publishes. *Constitution
  III; FR-015.* **[ratified]**
- **R-003** — No dependency on an activity log. Fire a domain event; the activity-log listener is
  optional and registered only when `spatie/laravel-activitylog` is installed and enabled.
  *Constitution III; FR-017, FR-018.* **[ratified]**
- **R-004** — No hard-coded column, table, panel id, route path or word. Six columns, the token
  table, token lifetime, invite path, return panel: config. Words: publishable `en` and `ms`.
  *Constitution III; FR-014.* **[ratified]**
- **R-005** — Host policy enters only through the three hooks: "password changed", "skip
  enforcement", "password rules (and their presentation)". Do not import or reimplement the
  source application's impersonation, session or password-meter code. *Constitution III;
  FR-016.* **[ratified]**
- **R-006** — Out of scope: two-factor **enrolment** and its lifecycle events; self-service
  password reset; impersonation itself. *Spec § Assumptions.* **[ratified]**

## Secrets

- **R-007** — Never transmit or store a password in clear; never put a password, token,
  two-factor secret or recovery code into an event, log line, notification body, audit entry,
  exception message, or clear queue payload. *Constitution II; FR-005, FR-017, SC-001.* **[ratified]**
- **R-008** — Events carry identifiers and plain values only — never a model. *Constitution II;
  FR-017.* **[ratified]**
- **R-009** — Every parameter that carries a secret is `#[SensitiveParameter]`. *Constitution II.*
  **[ratified]**
- **R-010** — Invite tokens live in their own table, never the password-reset table; a new invite
  invalidates the old. Port the source's token-independence test. *Constitution II; FR-002;
  source `tests/Feature/Accounts/TokenIndependenceTest.php`, `config/auth.php:102-125`.* **[inherited]**
- **R-011** — The queued invite notification is encrypted on the queue. *FR-005.* **[ratified]**

## Never lock a person out

- **R-012** — The forced-change flag defaults to **false** in the stub. *Constitution IV; FR-011.*
  **[ratified]**
- **R-013** — A password the person chose never arms the forced change; one set by someone else
  does. Assert both. *Constitution IV; FR-004, FR-007; source `CompleteProvisioning.php:21-25`.*
  **[inherited]**
- **R-014** — The trap lets through: the change page, sign-out, two-factor set-up, the background
  round trips those pages need (match the Livewire update route by name, any prefix), and
  whatever the skip hook allows. A skipped session cannot complete the change. *Constitution IV;
  FR-008, FR-010; source `app/Http/Middleware/EnforcePasswordChange.php`.* **[inherited]**
- **R-015** — Completing the change keeps the current session signed in (refresh the session's
  password hash for the guard). *FR-009; source `CompletesForcedPasswordChange.php` save().*
  **[inherited]**
- **R-016** — Account creation never depends on mail: the invite is queued, and a mail failure
  does not roll the account back. *Constitution IV; FR-001.* **[ratified]**
- **R-017** — Two-factor reset has one code path for the admin action and the console command.
  *Constitution IV; FR-012.* **[ratified]**

## Compatibility with the consumer

- **R-018** — Keep the five audit event names exactly: `provisioning.sent`,
  `provisioning.resent`, `provisioning.completed`, `password.force_change_completed`,
  `2fa.reset` (property `via`: `admin` | `console`). The consumer's activity viewer and its
  translations depend on them. *Spec SC-004; source `ActivityDetailPresenter.php:143`.* **[inherited]**
- **R-019** — The sign-in record is one update: increment the count and stamp the time together.
  *FR-013; source `RecordSuccessfulLogin.php`.* **[inherited]**

## Tests and quality

- **R-020** — Test first; see the test fail for the right reason before the code exists; a guard
  never seen red is unverified. *Constitution I.* **[ratified]**
- **R-021** — Ported tests keep their intent; the consumer keeps its own copies unchanged as
  adoption proof. *Constitution I; SC-004.* **[ratified]**
- **R-022** — Three suites: core (run in CI with Filament **uninstalled**), bridge (a panel with
  the plugin and one without), browser (accept page and change page in light and dark).
  *Constitution V, Workflow.* **[pending artifact]** — `phpunit.xml.dist`, `.github/workflows/tests.yml`.
- **R-023** — Random order; fail on warnings, risky tests and output; tests never print.
  *Constitution I.* **[pending artifact]** — `phpunit.xml.dist`.
- **R-024** — PHPStan over `src` and `config` at a level never lowered; no new baseline entries.
  Pint owns formatting. *Constitution, Platform.* **[pending artifact]** — `phpstan.neon.dist`, `pint.json`.
- **R-025** — CI actions pinned to a full SHA with the version in a comment; least-privilege
  `permissions:` per workflow. *Constitution, Platform.* **[pending artifact]** — workflow file.
- **R-026** — PHP ^8.3, `illuminate/*` ^13, Filament v5 for the bridge; do not widen without a
  host that needs it. *Constitution, Platform.* **[pending artifact]** — `composer.json`.
- **R-027** — `.gitattributes` `export-ignore` for specs, agent files, tests and build sources
  before the first tag. *Constitution VI; FR-020.* **[pending artifact]** — `.gitattributes`.

## Workflow

- **R-028** — A quoted constraint is a claim: verify any "already handled / forbidden / fixed"
  against the artifact before relying on it. The PKG-02 inventory was wrong in twelve places.
  *Constitution, Workflow.* **[ratified]**
- **R-029** — Visual work is checked in a real browser in light and dark before it is called
  done. *Constitution, Workflow.* **[ratified]**
- **R-030** — No tag until the consumer has been built against the package unpublished and its
  ported tests pass unchanged. *Constitution, Release.* **[ratified]**
- **R-031** — Conventional Commits, lowercase subjects; no commit, push, tag or release unless
  asked; report outcomes as they are. *Constitution, Workflow.* **[ratified]**

| Principle | Rules |
|---|---|
| I. Test-first | R-020, R-021, R-023 |
| II. No secret leaves | R-007…R-011 |
| III. Host owns words, storage, audit, policy | R-002…R-006, R-018 |
| IV. Never lock a person out | R-012…R-017, R-019 |
| V. Core is Laravel | R-001, R-022 |
| VI. Ship runtime only | R-027 |
| Platform / Workflow | R-024…R-026, R-028…R-031 |
