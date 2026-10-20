<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Rolland\Provisioning\Notifications\ProvisioningInvite;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;

/*
 * T029 — the accept page from a real invite link, in a real browser: styled by the
 * panel's theme (constitution VI), usable, and free of serious accessibility issues
 * in both schemes. axe proves names EXIST, not that they read well.
 */

function inviteLinkPath(): string
{
    $user = BridgeUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'placeholder-123']);
    $token = Password::broker('provisioning')->getRepository()->create($user);
    $url = (new ProvisioningInvite($token))->toMail($user)->viewData['actionUrl'];

    return parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);
}

it('renders styled by the panel theme, not as bare HTML', function (): void {
    $page = visit(inviteLinkPath());

    $page->assertSee(__('provisioning::provisioning.invite.heading'));

    expect($page->script("getComputedStyle(document.querySelector('.fi-btn')).backgroundColor"))
        ->not->toBe('rgba(0, 0, 0, 0)');
});

it('sets the password and lands on the panel sign-in', function (): void {
    $page = livewireReady(visit(inviteLinkPath()));

    typeInto($page, 'form.password', 'a-brand-new-Secret-1');
    typeInto($page, 'form.passwordConfirmation', 'a-brand-new-Secret-1');
    $page->click('button[type="submit"]');

    arrivesAt($page, '/admin/login');

    expect(Hash::check('a-brand-new-Secret-1', BridgeUser::query()->firstOrFail()->password))->toBeTrue();
});

it('has no accessibility issues in light or dark', function (string $scheme): void {
    $page = visit(inviteLinkPath());

    ($scheme === 'dark' ? $page->inDarkMode() : $page->inLightMode())->assertNoAccessibilityIssues();
})->with(['light', 'dark']);
