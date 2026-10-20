<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Rolland\Provisioning\Filament\Pages\AcceptInvite;
use Rolland\Provisioning\Notifications\ProvisioningInvite;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;

/*
 * The page half of the source application's AcceptProvisioningInviteTest: password
 * rules, confirmation, refusal message, return to the accepting panel's login — and
 * the mailed link actually opening it (FR-003, FR-004).
 */

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->user = BridgeUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'placeholder-123']);
    $this->token = Password::broker('provisioning')->getRepository()->create($this->user);
});

it('opens from the link in the invite mail', function (): void {
    $url = (new ProvisioningInvite($this->token))->toMail($this->user)->viewData['actionUrl'];

    $this->get($url)->assertOk()->assertSee(__('provisioning::provisioning.invite.heading'));
});

it('refuses a tampered link before looking at the token', function (): void {
    $url = (new ProvisioningInvite($this->token))->toMail($this->user)->viewData['actionUrl'];

    $this->get(str_replace('ada%40example.test', 'eve%40example.test', $url))->assertForbidden();
});

it('sets the password and returns to the panel\'s sign-in', function (): void {
    Livewire::test(AcceptInvite::class, ['token' => $this->token, 'email' => $this->user->email])
        ->fillForm(['password' => 'a-brand-new-Secret-1', 'passwordConfirmation' => 'a-brand-new-Secret-1'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getPanel('admin')->getLoginUrl());

    expect(Hash::check('a-brand-new-Secret-1', $this->user->refresh()->password))->toBeTrue()
        ->and($this->user->force_password_change)->toBeFalse();
});

it('refuses a mismatched confirmation or a password the rules reject', function (string $password, string $confirmation): void {
    Livewire::test(AcceptInvite::class, ['token' => $this->token, 'email' => $this->user->email])
        ->fillForm(['password' => $password, 'passwordConfirmation' => $confirmation])
        ->call('save')
        ->assertHasFormErrors();

    expect(Hash::check($password, $this->user->refresh()->password))->toBeFalse();
})->with([
    'mismatch' => ['a-brand-new-Secret-1', 'something-else-2'],
    'too short' => ['short', 'short'],
]);

it('says the invite is no longer valid for a used token', function (): void {
    Livewire::test(AcceptInvite::class, ['token' => 'bogus', 'email' => $this->user->email])
        ->fillForm(['password' => 'a-brand-new-Secret-1', 'passwordConfirmation' => 'a-brand-new-Secret-1'])
        ->call('save')
        ->assertHasFormErrors(['password']);
});
