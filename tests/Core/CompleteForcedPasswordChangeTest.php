<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Rolland\Provisioning\Actions\CompleteForcedPasswordChange;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Contracts\HandlesPasswordChange;
use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * The source application's CompleteForcedPasswordChange, with its two couplings turned
 * into hooks: ending other sessions (Security/PasswordChangeSessionTest there) is now
 * `HandlesPasswordChange`; refusing an impersonator is `DecidesEnforcementSkip`.
 * FR-009, FR-010, constitution III.
 */

function flaggedUser(): User
{
    return User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1', 'force_password_change' => true, 'remember_token' => 'remembered-under-the-admin-password']);
}

it('rotates the remember token, so a remember-me login made with the old password dies', function (): void {
    $user = flaggedUser();

    app(CompleteForcedPasswordChange::class)->handle($user, 'my-own-new-secret-1');

    expect($user->refresh()->remember_token)->not->toBe('remembered-under-the-admin-password')
        ->and($user->remember_token)->not->toBeEmpty();
});

it('saves the new password, clears the flag, stamps the time, runs the hook once, and reports it', function (): void {
    Event::fake([ForcedPasswordChangeCompleted::class]);
    $this->freezeTime();

    $calls = [];
    app()->instance(HandlesPasswordChange::class, new class($calls) implements HandlesPasswordChange
    {
        public function __construct(private array &$calls) {}

        public function passwordChanged(Authenticatable $user, string $newPassword): void
        {
            $this->calls[] = [$user->getAuthIdentifier(), $newPassword];
        }
    });

    $user = flaggedUser();

    app(CompleteForcedPasswordChange::class)->handle($user, 'my-own-new-secret-1');

    $user->refresh();
    expect(Hash::check('my-own-new-secret-1', $user->password))->toBeTrue()
        ->and($user->force_password_change)->toBeFalse()
        ->and($user->password_changed_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($calls)->toBe([[$user->getKey(), 'my-own-new-secret-1']]);

    Event::assertDispatched(ForcedPasswordChangeCompleted::class, fn (ForcedPasswordChangeCompleted $event): bool => $event->userId === $user->getKey());
});

it('refuses a new password identical to the current one and changes nothing', function (): void {
    $user = flaggedUser();

    expect(fn () => app(CompleteForcedPasswordChange::class)->handle($user, 'set-by-an-admin-1'))
        ->toThrow(ValidationException::class, __('provisioning::provisioning.force_change.same_as_current'));

    expect($user->refresh()->force_password_change)->toBeTrue()
        ->and($user->password_changed_at)->toBeNull();
});

it('refuses a session the host says may not complete the change', function (): void {
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

    $user = flaggedUser();

    expect(fn () => app(CompleteForcedPasswordChange::class)->handle($user, 'my-own-new-secret-1'))
        ->toThrow(ValidationException::class);

    expect(Hash::check('my-own-new-secret-1', $user->refresh()->password))->toBeFalse()
        ->and($user->force_password_change)->toBeTrue();
});
