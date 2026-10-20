<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Rolland\Provisioning\Actions\AcceptInvite;
use Rolland\Provisioning\Actions\ArmForcedPasswordChange;
use Rolland\Provisioning\Actions\CompleteForcedPasswordChange;
use Rolland\Provisioning\Actions\ResetTwoFactor;
use Rolland\Provisioning\Actions\SendInvite;
use Rolland\Provisioning\Http\Middleware\EnforcePasswordChange;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * SC-005, FR-014, constitution III: a host renames all six columns and the token
 * table; the published stubs create those names and every flow uses them. The
 * default-named columns from the fixture migrations stay as decoys — a flow still
 * hard-coding a default name writes there and the assertions below catch it.
 */

beforeEach(function (): void {
    config([
        'provisioning.columns' => [
            'force_password_change' => 'must_change_pw',
            'password_changed_at' => 'pw_changed_on',
            'last_login_at' => 'seen_on',
            'login_count' => 'visits',
            'two_factor_secret' => 'otp_seed',
            'two_factor_recovery_codes' => 'otp_backup',
        ],
        'provisioning.broker.table' => 'invitations',
        'auth.passwords.provisioning.table' => 'invitations',
    ]);

    // Re-run the package's stubs as a host would, now with the renamed config.
    (require dirname(__DIR__, 2).'/database/migrations/add_provisioning_columns_to_users_table.php.stub')->up();
    (require dirname(__DIR__, 2).'/database/migrations/create_provisioning_tokens_table.php.stub')->up();

    Schema::table('users', function ($table): void {
        $table->text('otp_seed')->nullable();
        $table->text('otp_backup')->nullable();
    });

    $this->user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'set-by-an-admin-1']);
});

function rawUser(): object
{
    return DB::table('users')->where('email', 'ada@example.test')->first();
}

it('creates the renamed columns with a safe default, and the renamed token table', function (): void {
    expect(Schema::hasColumns('users', ['must_change_pw', 'pw_changed_on', 'seen_on', 'visits']))->toBeTrue()
        ->and(Schema::hasTable('invitations'))->toBeTrue()
        ->and((bool) rawUser()->must_change_pw)->toBeFalse();
});

it('invites and accepts through the renamed token table', function (): void {
    Notification::fake();

    app(SendInvite::class)->handle($this->user, null);

    expect(DB::table('invitations')->where('email', 'ada@example.test')->exists())->toBeTrue()
        ->and(DB::table('provisioning_tokens')->count())->toBe(0);

    $token = Password::broker('provisioning')->getRepository()->create($this->user);
    expect(app(AcceptInvite::class)->handle('ada@example.test', $token, 'my-own-new-secret-1'))->toBeTrue();
});

it('arms, enforces and completes the forced change on the renamed columns', function (): void {
    app(ArmForcedPasswordChange::class)->handle($this->user);
    expect((bool) rawUser()->must_change_pw)->toBeTrue()
        ->and((bool) rawUser()->force_password_change)->toBeFalse();

    Route::middleware(['web', EnforcePasswordChange::class])->get('home', fn () => 'home');
    Route::get('change-password', fn () => 'change')->name('password.change');
    config(['provisioning.enforcement.redirect_route' => 'password.change']);
    $this->actingAs($this->user->refresh())->get('/home')->assertRedirect(route('password.change'));

    app(CompleteForcedPasswordChange::class)->handle($this->user->refresh(), 'my-own-new-secret-1');
    $row = rawUser();
    expect((bool) $row->must_change_pw)->toBeFalse()
        ->and($row->pw_changed_on)->not->toBeNull()
        ->and($row->password_changed_at)->toBeNull();
});

it('records sign-ins on the renamed columns', function (): void {
    Auth::attempt(['email' => 'ada@example.test', 'password' => 'set-by-an-admin-1']);

    $row = rawUser();
    expect((int) $row->visits)->toBe(1)
        ->and($row->seen_on)->not->toBeNull()
        ->and((int) $row->login_count)->toBe(0)
        ->and($row->last_login_at)->toBeNull();
});

it('resets two-factor on the renamed columns', function (): void {
    DB::table('users')->where('email', 'ada@example.test')->update([
        'otp_seed' => 'SEED', 'otp_backup' => 'codes', 'app_authentication_secret' => 'DECOY',
    ]);

    app(ResetTwoFactor::class)->handle($this->user->refresh(), null);

    $row = rawUser();
    expect($row->otp_seed)->toBeNull()
        ->and($row->otp_backup)->toBeNull()
        ->and($row->app_authentication_secret)->toBe('DECOY');
});
