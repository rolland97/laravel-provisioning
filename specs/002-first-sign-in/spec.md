# Feature Specification: First sign-in (v0.2.0)

**Branch**: `002-first-sign-in` · **Created**: 2026-11-13 · **Status**: Draft

**Input**: the consumer's slice 139 (`ew-global-sdn-bhd/procument`,
`specs/139-first-sign-in-polish/`), from the owner's first sign-in on its production server on
2026-11-13. Two defects on the path every new account walks:

1. A person whose password was set by someone else follows a **password reset link**, chooses a
   password, signs in — and is sent to "Set a new password" again, told the password "was set by an
   administrator". Typing the password they have just chosen is refused as "the same as the current
   one".
2. "Set a new password" opens inside the full panel (sidebar, top bar, search), although the person
   can go nowhere until they finish it.

## Requirements

- **FR-001**: A completed password reset (Laravel's `PasswordReset` event, which fires only after a
  successful reset) MUST clear the forced-change column and stamp the password-changed-at column,
  through the configured column names. When the requirement was armed, it MUST dispatch
  `ForcedPasswordChangeCompleted`, so the activity record shows the completion. A failed reset MUST
  change nothing.
- **FR-002**: `ArmForcedPasswordChange` and every other way of arming the requirement are unchanged.
- **FR-003**: The behaviour is on by default and can be switched off with
  `provisioning.clear_on_password_reset` (as `record_logins` is).
- **FR-004**: The Filament forced-change page MUST use Filament's simple (card) layout — logo,
  heading, the form, a full-width submit — with no sidebar or top bar; signed in, the simple layout's
  own user menu offers sign-out. Its route, URL, name, fields, rules and redirects are unchanged.
- **FR-005**: Core suite (Filament removed), bridge suite, browser suite (light and dark), PHPStan
  and Pint pass; the consumer is built against this branch unpublished before the tag (constitution,
  Release).

## Success criteria

- **SC-001**: A new account chooses its password once, through a reset link, and reaches the panel.
- **SC-002**: The forced-change page shows no navigation.

## Out of scope

Anything else in the consumer's slice 139 (its beta notice).
