# Changelog

## v0.2.0 — 2026-11-13

- **A completed password reset clears the forced change** and stamps `password_changed_at`;
  when the change was armed, `ForcedPasswordChangeCompleted` fires. Before, a person whose
  password an administrator had set chose a password through a reset link and was then told to
  choose one again, and the same password was refused. Switch: `clear_on_password_reset`
  (default `true`).
- **The forced-change page is a card page** (Filament's simple layout): no sidebar or top bar;
  the simple layout's user menu signs out. Its route, URL, fields and rules are unchanged.

## v0.1.0

First release, extracted from its source application.
