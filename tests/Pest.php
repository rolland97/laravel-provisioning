<?php

declare(strict_types=1);

use Rolland\Provisioning\Tests\BridgeTestCase;
use Rolland\Provisioning\Tests\BrowserTestCase;
use Rolland\Provisioning\Tests\TestCase;

// ⚠️ `Core` gets the Filament-free base class, and nothing else does: the core suite
// runs in CI with filament/filament UNINSTALLED (AGENTS.md R-022).
uses(TestCase::class)->in('Core');
uses(BridgeTestCase::class)->in('Bridge');
uses(BrowserTestCase::class)->in('Browser');

/**
 * Wait until Livewire has booted the page's component. Typing earlier lands in inputs
 * whose `wire:model` is not bound yet, so the form submits empty — on a slow CI runner
 * that looked like a page that "never left" (first CI run, 2026-10-20).
 */
function livewireReady(mixed $page, int $attempts = 40): mixed
{
    for ($attempt = 0; $attempt < $attempts; $attempt++) {
        if ((bool) $page->script('!! (window.Livewire && window.Livewire.all().length > 0)')) {
            return $page;
        }

        $page->wait(0.25);
    }

    expect(false)->toBeTrue('Livewire never booted the page.');

    return $page;
}

/**
 * Wait for the browser to reach `$path` after a submit; when it does not, fail with the
 * form's own validation messages, so the reason is in the log rather than a screenshot.
 */
function arrivesAt(mixed $page, string $path, int $attempts = 40): mixed
{
    for ($attempt = 0; $attempt < $attempts; $attempt++) {
        if ($page->script('window.location.pathname') === $path) {
            return $page->assertPathIs($path);
        }

        $page->wait(0.25);
    }

    $errors = $page->script("document.body.innerText.replace(/\\s+/g, ' ').trim().slice(0, 600) + ' || values: ' + [...document.querySelectorAll('input')].map(i => i.id + '=' + i.value.length).join(',')");

    expect($page->script('window.location.pathname'))->toBe($path, 'Still on the form. It reads: '.($errors !== '' ? $errors : '(none)'));

    return $page;
}

/**
 * Type into a field and make sure the value stuck. A revealable password input finishes
 * its Alpine set-up a moment after Livewire boots and can wipe what was typed before —
 * seen as `form.password` empty and its confirmation full, the form never submitting
 * (first CI run, 2026-10-20, and 1 local run in 6).
 */
function typeInto(mixed $page, string $id, string $value, int $attempts = 5): mixed
{
    $read = sprintf('document.getElementById(%s)?.value ?? null', json_encode($id, JSON_THROW_ON_ERROR));

    for ($attempt = 0; $attempt < $attempts; $attempt++) {
        $page->type('[id="'.$id.'"]', $value);
        $page->wait(0.2);

        if ($page->script($read) === $value) {
            return $page;
        }
    }

    expect($page->script($read))->toBe($value, "Typing into {$id} did not stick.");

    return $page;
}
