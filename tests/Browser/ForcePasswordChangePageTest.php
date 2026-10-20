<?php

declare(strict_types=1);

use Rolland\Provisioning\Tests\Fixtures\BridgeUser;

/*
 * T029 — the trap and the change page in a real browser: an armed user lands on the
 * change page, saves, and is let into the panel; the page is styled and accessible.
 */

beforeEach(function (): void {
    $this->user = BridgeUser::query()->create([
        'name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1', 'force_password_change' => true,
    ]);
    $this->actingAs($this->user);
});

it('sends an armed user to the change page, then lets them in once it is saved', function (): void {
    $page = visit('/admin');

    $page->assertPathIs('/admin/force-password-change')
        ->type('[id="form.current_password"]', 'set-by-an-admin-1')
        ->type('[id="form.password"]', 'my-own-new-Secret-12')
        ->type('[id="form.password_confirmation"]', 'my-own-new-Secret-12')
        // Scoped to the page's form: the user menu's "Sign out" is a submit button too.
        ->click('.fi-main form.fi-form button[type="submit"]')
        ->assertPathIs('/admin');

    expect($this->user->refresh()->force_password_change)->toBeFalse();
});

it('has no accessibility issues in light or dark', function (string $scheme): void {
    $page = visit('/admin/force-password-change');

    ($scheme === 'dark' ? $page->inDarkMode() : $page->inLightMode())->assertNoAccessibilityIssues();
})->with(['light', 'dark']);
