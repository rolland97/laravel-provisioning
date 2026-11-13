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
    $page = livewireReady(visit('/admin'));

    $page->assertPathIs('/admin/force-password-change');
    typeInto($page, 'form.current_password', 'set-by-an-admin-1');
    typeInto($page, 'form.password', 'my-own-new-Secret-12');
    typeInto($page, 'form.password_confirmation', 'my-own-new-Secret-12');
    // Scoped to the page's form (id "form"): the user menu's "Sign out" is a submit button too.
    $page->click('form#form button[type="submit"]');

    arrivesAt($page, '/admin');

    expect($this->user->refresh()->force_password_change)->toBeFalse();
});

it('is a card page with no navigation, and the user menu signs out (002)', function (): void {
    $page = livewireReady(visit('/admin/force-password-change'));

    $page->assertPresent('.fi-simple-layout')
        ->assertMissing('.fi-sidebar')
        ->assertMissing('.fi-topbar');

    $page->click('.fi-user-menu-trigger');
    $page->click('.fi-dropdown-panel form[action$="logout"] button[type="submit"]');

    arrivesAt($page, '/admin/login');
    expect(auth()->check())->toBeFalse();
});

it('has no accessibility issues in light or dark', function (string $scheme): void {
    $page = visit('/admin/force-password-change');

    ($scheme === 'dark' ? $page->inDarkMode() : $page->inLightMode())->assertNoAccessibilityIssues();
})->with(['light', 'dark']);
