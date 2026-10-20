# laravel-provisioning — session orientation

Package `rolland97/laravel-provisioning`: admin-created accounts (invite, accept, forced
password change, two-factor reset) for Laravel 13, with an optional Filament 5 plugin.
Extracted from `ew-global-sdn-bhd/procument` (local checkout `~/projects/procurement`),
which is its one consumer and the release gate.

Read first, in order:

1. `.specify/memory/constitution.md` — six principles; they win over any practice.
2. `AGENTS.md` — the operational rules, loaded before every `/speckit-plan`.
3. `specs/001-provisioning-v1/` — spec, plan, research (R1–R10), tasks, validation log.

Spec Kit 1.0.1 with the Superpowers Bridge and MemoryLint extensions, as in
`rolland97/laravel-tree`. `.claude/` and most of `.specify/` are reinstallable tooling and
are not tracked: `specify init --here --force --integration claude --script sh`, then
`specify extension add superb` and `specify extension add memorylint`.

Suites: `core` (also run with filament/filament and spatie/laravel-activitylog removed),
`bridge` (an `admin` panel with the plugin, a `plain` panel without), `browser`
(Playwright 1.64 — the consumer's version; Pest 5, because Pest 4's browser plugin hangs
on Playwright after 1.61). Run `composer test:browser` on its own, never inside a
parallel run.

Release: no tag until the consumer has been built against this package unpublished and
its own provisioning tests pass unchanged (constitution, Release).
