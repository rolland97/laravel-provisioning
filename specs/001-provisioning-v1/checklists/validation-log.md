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

## T035 — first CI run (GitHub run 39373651220, `aadf400`): 2 causes, both fixed

1. **PHP 8.3 legs could not install**: Pest 5 requires PHP ^8.4. The floor moves to 8.4 by
   constitution amendment 1.1.0 (composer, CI matrix 8.4/8.5, AGENTS R-026, plan, README).
2. **Browser "sets the password and lands on the panel sign-in" stayed on the form.**
   Reproduced locally (1 run in 6) once a helper reported the page's state: `form.password`
   was EMPTY while its confirmation held all 20 characters — the revealable password input
   finishing its Alpine set-up after Livewire booted wiped the first typed value, so the form
   submitted without a password. The same cause explains the earlier unexplained local failure
   of the change-page walk. Fix in the tests (`typeInto()` types, waits and re-checks;
   `livewireReady()`; `arrivesAt()` reports the page text and field lengths on failure).
   Browser suite after the fix: **8 consecutive local runs 7/7**. Not a product defect: a
   person would have to type within a fraction of a second of the page loading.

## Correction (2026-10-20): the browser suite exited 1 while every test passed

The T034 entry above recorded the browser suite as passing from its "Tests: 7 passed" line;
its **exit code was 1**, which nobody looked at until CI run 39374301355 failed with all 7
tests green. Cause (PHPUnit `--log-events-text`): pestphp/pest-plugin-browser 5.1 calls
`@rmdir(tests/Browser/Traces)` at start-up; with the directory absent PHP warns, PHPUnit 13
records the warning despite the `@`, and `failOnWarning` turns it into exit 1. Fix:
`tests/bootstrap.php` creates the directory before the plugin cleans up (strictness kept).
After the fix, by exit code: browser 0 (three consecutive runs), core+bridge 0, phpstan 0.
⚠️ Lesson recorded: a gate is its exit status, not a grep of its summary line.

## T035 — CI green (GitHub run 39374718877, `cdcd4b7`)

All 7 jobs passed: PHP 8.4/8.5 × prefer-lowest/prefer-stable (core + bridge), phpstan · pint,
the browser suite (Playwright), and the core suite with filament/filament and
spatie/laravel-activitylog uninstalled. Repository public at
https://github.com/rolland97/laravel-provisioning (default branch `001-provisioning-v1`, no tag).

## T036 — critique (2026-10-20)

**Critical: none. Important: none open** (the two security-review findings are fixed above).
Minor, recorded rather than changed:

1. `acceptsInvites()` writes `provisioning.invite.route` at panel registration, so with two
   accepting panels the last registered wins — the README says to register it on one panel.
2. `SendInviteAction` is labelled "Resend invite" even for a first send from an edit page; the
   consumer only ever offers it as a re-send (before first sign-in), so the wording matches its
   use. A host wanting "Send" relabels with `->label()`.
3. Brute force on the accept page needs a valid signature on the link first (the route checks it
   before the token), and tokens are 64 random hex characters; the broker's `throttle` limits
   re-issue, not guesses — acceptable because guesses never reach the broker unsigned.

FR-001 (unusable password on create) is documented as host code in the README with the
recipe the consumer uses; the package provides `SendInvite` and `ArmForcedPasswordChange`
for both branches. Every other FR and SC has a test listed in tasks.md.

## T037 — owner review

Rolland, 2026-10-20: public API approved ("approve, proceed with care"). Next: the
consumer's adoption slice; no tag until it builds against this branch with its own tests'
assertions unchanged.
