<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Http\Middleware\EnforcePasswordChange;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported in intent from the source application's EnforcePasswordChangeTest (FR-008,
 * constitution IV) — the core middleware, on plain routes. The Filament panel variant
 * is covered in the bridge suite.
 */

beforeEach(function (): void {
    Route::middleware(['web', 'auth', EnforcePasswordChange::class])->group(function (): void {
        Route::get('home', fn () => 'home')->name('home');
        Route::get('login', fn () => 'login')->withoutMiddleware(['auth', EnforcePasswordChange::class])->name('login');
        Route::get('change-password', fn () => 'change')->name('password.change');
        Route::post('logout', fn () => 'bye')->name('logout');
        Route::post('livewire/update', fn () => 'lw')->name('default-livewire.update');
    });

    config(['provisioning.enforcement.redirect_route' => 'password.change']);
    config(['provisioning.enforcement.except' => ['*livewire.update', 'logout', 'password.change']]);
});

function armedUser(bool $armed = true): User
{
    return User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123', 'force_password_change' => $armed]);
}

it('sends an armed user to the change page', function (): void {
    $this->actingAs(armedUser())->get('/home')->assertRedirect(route('password.change'));
});

it('leaves an unarmed user alone', function (): void {
    $this->actingAs(armedUser(false))->get('/home')->assertOk()->assertSee('home');
});

it('lets an armed user reach the change page, sign out, and make Livewire round trips', function (string $method, string $uri): void {
    $this->actingAs(armedUser())->call($method, $uri)->assertOk();
})->with([
    'the change page' => ['GET', '/change-password'],
    'sign-out' => ['POST', '/logout'],
    'a Livewire update' => ['POST', '/livewire/update'],
]);

it('lets a request the host exempts through, and leaves the flag for the owner', function (): void {
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

    $user = armedUser();

    $this->actingAs($user)->get('/home')->assertOk();
    expect($user->refresh()->force_password_change)->toBeTrue();
});

it('does nothing for a guest', function (): void {
    $this->get('/home')->assertRedirect();
    expect(auth()->check())->toBeFalse();
});
