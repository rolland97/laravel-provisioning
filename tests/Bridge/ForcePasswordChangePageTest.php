<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Contracts\ProvidesPasswordRules;
use Rolland\Provisioning\Filament\Pages\ForcePasswordChange;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;

/*
 * The source application's ForcePasswordChange page behaviour (CompletesForcedPasswordChange
 * there), with its couplings behind the hooks. FR-009, FR-010, FR-016.
 */

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->user = BridgeUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1', 'force_password_change' => true]);
    $this->actingAs($this->user);
});

it('saves a new password, clears the flag and keeps the session signed in', function (): void {
    Livewire::test(ForcePasswordChange::class)
        ->fillForm([
            'current_password' => 'set-by-an-admin-1',
            'password' => 'my-own-new-Secret-12',
            'password_confirmation' => 'my-own-new-Secret-12',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getUrl());

    $this->user->refresh();
    expect(Hash::check('my-own-new-Secret-12', $this->user->password))->toBeTrue()
        ->and($this->user->force_password_change)->toBeFalse()
        ->and(session('password_hash_'.Filament::getAuthGuard()))->toBe($this->user->getAuthPassword());
});

it('refuses a wrong current password, the same password, and a mismatched confirmation', function (array $data): void {
    Livewire::test(ForcePasswordChange::class)->fillForm($data)->call('save')->assertHasFormErrors();

    expect($this->user->refresh()->force_password_change)->toBeTrue();
})->with([
    'wrong current' => [['current_password' => 'not-it', 'password' => 'my-own-new-Secret-12', 'password_confirmation' => 'my-own-new-Secret-12']],
    'same as current' => [['current_password' => 'set-by-an-admin-1', 'password' => 'set-by-an-admin-1', 'password_confirmation' => 'set-by-an-admin-1']],
    'mismatch' => [['current_password' => 'set-by-an-admin-1', 'password' => 'my-own-new-Secret-12', 'password_confirmation' => 'something-else-34']],
]);

it('applies the host\'s password rules', function (): void {
    app()->instance(ProvidesPasswordRules::class, new class implements ProvidesPasswordRules
    {
        public function rules(): array
        {
            return ['min:40'];
        }

        public function requirementsView(): ?string
        {
            return null;
        }
    });

    Livewire::test(ForcePasswordChange::class)
        ->fillForm(['current_password' => 'set-by-an-admin-1', 'password' => 'my-own-new-Secret-12', 'password_confirmation' => 'my-own-new-Secret-12'])
        ->call('save')
        ->assertHasFormErrors(['password']);
});

it('sends an unarmed user back to the panel', function (): void {
    $this->user->forceFill(['force_password_change' => false])->save();

    Livewire::test(ForcePasswordChange::class)->assertRedirect(Filament::getUrl());
});

it('refuses a session the host blocks, leaving the change for the owner', function (): void {
    app()->bind(DecidesEnforcementSkip::class, fn () => new class implements DecidesEnforcementSkip
    {
        public function shouldSkip(Request $request, Authenticatable $user): bool
        {
            return true;
        }

        public function blocksCompletion(Authenticatable $user): bool
        {
            return true;
        }

        public function blockedRedirectUrl(Authenticatable $user): ?string
        {
            return null;
        }
    });

    Livewire::test(ForcePasswordChange::class)->assertRedirect(Filament::getUrl());
    expect($this->user->refresh()->force_password_change)->toBeTrue();
});

it('sends a blocked session where the host says, when it names a place', function (): void {
    app()->bind(DecidesEnforcementSkip::class, fn () => new class implements DecidesEnforcementSkip
    {
        public function shouldSkip(Request $request, Authenticatable $user): bool
        {
            return true;
        }

        public function blocksCompletion(Authenticatable $user): bool
        {
            return true;
        }

        public function blockedRedirectUrl(Authenticatable $user): ?string
        {
            return '/somewhere-the-host-chose';
        }
    });

    Livewire::test(ForcePasswordChange::class)->assertRedirect('/somewhere-the-host-chose');
    expect($this->user->refresh()->force_password_change)->toBeTrue();
});
