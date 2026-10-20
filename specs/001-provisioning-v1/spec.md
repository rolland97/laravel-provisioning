# Feature Specification: laravel-provisioning v1 — invite, accept, forced change, two-factor reset

**Feature Branch**: `001-provisioning-v1`

**Created**: 2026-10-20

**Status**: Draft

**Input**: Owner, 2026-10-20: extract the procurement application's account-provisioning
feature into a public package, `rolland97/laravel-provisioning` (PKG-02 in the application's
`docs/packages/`), built spec-first as `rolland97/laravel-tree` was. Source of truth for current
behaviour: the application at `dev6` `9429aa53`, re-audited the same day (PKG-02 § Re-audited
2026-10-20).

## Why this package exists

Every internal tool with admin-created accounts rebuilds this flow, usually worse: a
plaintext password mailed to the user, a forced change armed on a password the user chose
themselves, a two-factor reset that writes the secret into an audit log, or a trap page the
user cannot leave. The source application has the flow right, and has paid for several of
those mistakes already (an invite table shared with password resets, a trap with no escape
for two-factor set-up). The package keeps the flow and its guarantees and hands every
decision a host makes differently — columns, words, audit, sessions, impersonation, password
rules — back to the host.

Actors: an **administrator** who creates and manages accounts; the **person** whose account it
is; an **operator** with console access when no administrator can sign in; the **host
application** that installs the package.

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Invite a person instead of choosing their password (Priority: P1)

An administrator creates an account without a password. The person receives an invite, opens
it, sets their own password and signs in. Nobody else ever knew it.

**Why this priority**: it is the package's reason to exist and the most security-sensitive path.

**Independent Test**: create an account with no password, capture the invite, accept it with a
new password, sign in with that password; then try the same invite again and an expired one.

**Acceptance Scenarios**:

1. **Given** an administrator creates an account without a password, **When** it is saved,
   **Then** the account is stored with an unusable password, an invite is queued to the person,
   and the account is created even if sending mail fails.
2. **Given** an invite, **When** the person opens its link and sets a password meeting the
   host's rules (entered twice), **Then** the password is saved, the forced change is **not**
   armed, any "remember me" token is rotated, and they are sent to sign in.
3. **Given** an invite already used, expired (default 7 days), or replaced by a newer one,
   **When** it is opened, **Then** it is refused with a message and nothing changes.
4. **Given** an administrator re-sends the invite to someone who has never signed in, **When**
   it is sent, **Then** the previous invite stops working.
5. **Given** a password-reset request for the same person, **When** either flow issues a token,
   **Then** the other flow's outstanding token is unaffected.
6. **Given** the queued invite, **When** the queue payload is inspected (e.g. on a queue
   dashboard), **Then** it does not show the token in clear.

---

### User Story 2 — A password someone else set must be changed first (Priority: P1)

An administrator sets or resets a person's password. At their next sign-in the person must
choose a new one before they can do anything else — but can always sign out, finish two-factor
set-up, or be let through by the host's own rule.

**Why this priority**: the trap is where a defect locks people out or lets a known password
stand.

**Independent Test**: arm the change, sign in, try other pages (redirected), sign out (allowed),
change the password (to the same one: refused; to a new one: done), then browse freely.

**Acceptance Scenarios**:

1. **Given** an administrator sets a password on an account, **When** it is saved, **Then** the
   forced change is armed; **given** the person sets their own (invite or self-service), it is not.
2. **Given** the change is armed, **When** the person requests any other page, **Then** they are
   sent to the change page; requests for the change page, sign-out, two-factor set-up, and the
   background round trips those pages need are let through.
3. **Given** the host's skip rule says this request may pass (the source application: a
   permitted impersonation), **When** it is evaluated, **Then** the request passes and the flag
   stays for the account's owner; the change page refuses to complete a change for such a session.
4. **Given** the change page, **When** the person submits their current password and a new one
   that meets the host's rules, differs from the current one and is entered twice, **Then** the
   password is saved, the flag cleared, the change time recorded, the host's "password changed"
   hook runs (the source application ends the person's other sessions), and the current session
   stays signed in.
5. **Given** a new password identical to the current one, **When** submitted, **Then** it is
   refused with a message and nothing changes.
6. **Given** the package is installed into an application with existing users, **When** its
   schema is added, **Then** nobody's forced change is armed.

---

### User Story 3 — Reset someone's two-factor, even with nobody left to sign in (Priority: P2)

An administrator clears a person's two-factor enrolment so they can enrol again. When the last
administrator is locked out, an operator does the same from the console.

**Why this priority**: lockout recovery; rarer than the first two, but with no other way out.

**Independent Test**: enrol, reset from the admin action, sign in and enrol again; repeat from the
console command.

**Acceptance Scenarios**:

1. **Given** an enrolled account, **When** an administrator resets its two-factor, **Then** the
   secret and recovery codes are cleared and the event records who did it and that it came from
   an administrator.
2. **Given** console access, **When** the operator runs the reset command for an account, **Then**
   the same reset happens through the same code path and the event records that it came from the
   console.
3. **Given** any reset, **When** its event or audit entry is read, **Then** it contains no secret
   and no recovery code.

---

### User Story 4 — Install into any Laravel application, with or without Filament (Priority: P2)

A host installs the package, publishes its schema stub and configuration, and uses it — through
the Filament panel plugin if it has Filament, or through the actions directly if it does not.

**Why this priority**: the package is only reusable if both kinds of host can adopt it.

**Independent Test**: install into a plain Laravel test application with Filament absent and run
every core action; install into a Filament panel with the plugin (pages and actions present) and
into a second panel without it (nothing added).

**Acceptance Scenarios**:

1. **Given** an application without Filament, **When** the package is installed, **Then** it loads,
   and inviting, accepting through the core action, arming, completing a change, resetting
   two-factor and the console command all work.
2. **Given** a Filament panel with the plugin, **When** it is used, **Then** it provides the
   accept-invite page, the change page with the trap, and the send/re-send invite and reset
   two-factor admin actions; a panel without the plugin is untouched.
3. **Given** the host renames every column the package uses (forced-change flag, password-changed
   time, last-sign-in time, sign-in count, two-factor secret, recovery codes) and the invite token
   table, **When** the flows run, **Then** they use the host's names.
4. **Given** a host that overrides the package's words (English and Malay ship), **When** pages and
   mail render, **Then** the host's words appear.

---

### User Story 5 — Keep an audit trail the host chooses (Priority: P3)

The host records what happened — invites sent and re-sent, invites accepted, forced changes
completed, two-factor resets — in its own audit log, with the event names the source
application already uses.

**Independent Test**: with the activity log installed, run each flow and read the entries; without
it, run them and see no error and no entries.

**Acceptance Scenarios**:

1. **Given** the activity log is installed and the optional listener enabled, **When** each flow
   completes, **Then** an entry is written named `provisioning.sent`, `provisioning.resent`,
   `provisioning.completed`, `password.force_change_completed` or `2fa.reset` (with `via`), against
   the account and naming who caused it.
2. **Given** no activity log, **When** the flows run, **Then** they work and write nothing.
3. **Given** a person signs in, **When** sign-in succeeds, **Then** their last sign-in time is
   recorded and their sign-in count goes up by one, in one update.

---

### Edge Cases

- An invite opened by someone other than its addressee (wrong email in the link): refused.
- A signed invite link that was tampered with: refused before the token is checked.
- The person is already signed in when they open an invite: the invite still sets the password
  of the account it names, never the signed-in one's.
- A forced change for an account whose guard is not session-based: the "password changed" hook
  still runs; ending other sessions is the host's business.
- Re-send for someone who has signed in before: not offered.
- Reset two-factor for an account not enrolled: not offered by the admin action; the console
  command reports it and changes nothing.
- A host whose users table already has `two_factor_*` columns from another package: the package
  uses whatever names the host configures and collides with nothing.

## Requirements *(mandatory)*

### Functional Requirements

**Invites**
- **FR-001**: Creating an account without a password MUST store an unusable password and queue an
  invite; account creation MUST NOT depend on mail succeeding.
- **FR-002**: Invite tokens MUST be single use, expire (default 7 days, configurable), be
  invalidated by a newer invite, and be stored apart from password-reset tokens.
- **FR-003**: The invite link MUST be signed and expire with the token; it MUST identify the
  account it is for, and accepting MUST set that account's password only.
- **FR-004**: Accepting MUST require a password meeting the host's rules, entered twice; it MUST
  NOT arm the forced change, and MUST rotate the "remember me" token.
- **FR-005**: The queued invite MUST NOT expose the token in clear in the queue payload.
- **FR-006**: Re-sending MUST be possible for an account that has never signed in.

**Forced change**
- **FR-007**: Setting a password for someone else MUST arm the forced change; a password the
  person chose MUST NOT. Arming MUST be available as an explicit package action.
- **FR-008**: While armed, every request MUST be redirected to the change page except: the change
  page, sign-out, two-factor set-up, the background round trips these pages need, and any request
  the host's skip rule allows.
- **FR-009**: Completing the change MUST require the current password, a new password meeting the
  host's rules that differs from the current one, entered twice; it MUST clear the flag, record
  the change time, run the host's "password changed" hook, and keep the current session signed in.
- **FR-010**: A session the host's skip rule lets through MUST NOT be able to complete the change.
- **FR-011**: The forced-change flag MUST default to off in the schema stub.

**Two-factor reset**
- **FR-012**: One action MUST clear an account's two-factor secret and recovery codes, recording
  the actor and whether it came from an administrator or the console; a console command MUST
  call the same action.

**Sign-in record**
- **FR-013**: A successful sign-in MUST record the time and increment the sign-in count in a
  single update.

**Host control**
- **FR-014**: Column names (six), the token table, the token lifetime, the invite route path, the
  panel the invite returns to, and all user-facing words (English and Malay shipped) MUST be
  configurable or overridable.
- **FR-015**: Schema MUST ship as stubs the host publishes; the package MUST run no migration.
- **FR-016**: The host MUST be able to supply: the "password changed" hook, the skip rule, and the
  password rules (and how they are presented on the change and invite pages).
- **FR-017**: The package MUST fire a domain event for each of: invite sent, invite re-sent,
  invite accepted, forced change completed, two-factor reset — carrying identifiers and plain
  values only, never a model or a secret.
- **FR-018**: An optional listener MUST write activity-log entries with the event names in User
  Story 5 when, and only when, the activity log is installed and the host enables it.

**Packaging**
- **FR-019**: Every core flow MUST work with Filament absent; the Filament plugin MUST add pages,
  trap and admin actions only to panels that register it.
- **FR-020**: The distribution MUST contain runtime files only, from the first release.

### Key Entities

- **Provisionable account**: the host's user, with the six configurable columns.
- **Invite token**: single-use, expiring, in its own table, one live per account.
- **Domain events**: invite sent / re-sent / accepted, forced change completed, two-factor reset —
  identifiers and plain values.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 0 places where a password, token, two-factor secret or recovery code appears in an
  event, a log line, an audit entry or the clear queue payload (checked by tests per flow).
- **SC-002**: Installing the package changes 0 existing users' ability to sign in.
- **SC-003**: Every core flow passes with Filament uninstalled; the plugin adds 0 routes, pages or
  middleware to a panel that does not register it.
- **SC-004**: The source application adopts the package with its existing provisioning tests
  passing unchanged, and its audit entries keep the same 5 event names.
- **SC-005**: A host can rename all 6 columns and the token table and every flow still passes.

## Assumptions

- Laravel 13 and Filament 5 (constitution, Platform); PHP 8.3+.
- Two-factor **enrolment** and its own lifecycle events (enabled, disabled, recovery regenerated)
  stay with the host or Filament; this package resets enrolment only.
- Impersonation, password-strength rules and the requirements meter stay in the host, reached
  through the hooks in FR-016; the source application keeps its own implementations.
- Self-service password reset (the host's `users` broker) is not part of this package; the package
  only guarantees not to interfere with it (FR-002).
- Released only after the source application has been built against it unpublished
  (constitution, Release).
