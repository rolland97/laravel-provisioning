<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Rolland\Provisioning\Actions\AcceptInvite;
use Rolland\Provisioning\Actions\CompleteForcedPasswordChange;
use Rolland\Provisioning\Actions\ResetTwoFactor;
use Rolland\Provisioning\Actions\SendInvite;
use Rolland\Provisioning\Tests\Fixtures\User;
use Spatie\Activitylog\Models\Activity;

/*
 * FR-018, AGENTS R-018: with spatie/laravel-activitylog installed, the optional
 * listener writes the SAME five event names the source application wrote, against the
 * account and naming who caused it — its activity viewer and translations key on them.
 */

beforeEach(function (): void {
    config(['provisioning.activity_log' => true]);

    // spatie/laravel-activitylog ships its migration as a stub, as this package does.
    (require dirname(__DIR__, 2).'/vendor/spatie/laravel-activitylog/database/migrations/create_activity_log_table.php.stub')->up();
});

function auditedUser(string $email = 'ada@example.test', array $attributes = []): User
{
    return User::query()->create(['name' => 'Ada', 'email' => $email, 'password' => 'set-by-an-admin-1', ...$attributes]);
}

function lastActivity(string $event): ?Activity
{
    return Activity::query()->where('event', $event)->latest('id')->first();
}

it('writes provisioning.sent and provisioning.resent against the account, caused by the admin', function (): void {
    Notification::fake();
    $user = auditedUser();
    $admin = auditedUser('admin@example.test');

    app(SendInvite::class)->handle($user, $admin);
    app(SendInvite::class)->handle($user, $admin, resend: true);

    foreach (['provisioning.sent', 'provisioning.resent'] as $event) {
        $entry = lastActivity($event);
        expect($entry)->not->toBeNull("no {$event} entry")
            ->and($entry->subject_id)->toBe($user->getKey())
            ->and($entry->causer_id)->toBe($admin->getKey())
            ->and($entry->description)->toBe($event);
    }
});

it('writes provisioning.completed caused by the person', function (): void {
    $user = auditedUser();
    $token = Password::broker('provisioning')->getRepository()->create($user);

    app(AcceptInvite::class)->handle($user->email, $token, 'a-brand-new-secret-1');

    expect(lastActivity('provisioning.completed')?->causer_id)->toBe($user->getKey());
});

it('writes password.force_change_completed caused by the person', function (): void {
    $user = auditedUser(attributes: ['force_password_change' => true]);

    app(CompleteForcedPasswordChange::class)->handle($user, 'my-own-new-secret-1');

    expect(lastActivity('password.force_change_completed')?->causer_id)->toBe($user->getKey());
});

it('writes 2fa.reset with how it was done, and no secret', function (?string $causerEmail, string $via): void {
    $user = auditedUser(attributes: ['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $causer = $causerEmail === null ? null : auditedUser($causerEmail);

    app(ResetTwoFactor::class)->handle($user, $causer);

    $entry = lastActivity('2fa.reset');
    expect($entry?->properties?->get('via'))->toBe($via)
        ->and($entry?->causer_id)->toBe($causer?->getKey())
        ->and(json_encode($entry?->toArray()))->not->toContain('JBSWY3DPEHPK3PXP');
})->with([
    'from an administrator' => ['admin@example.test', 'admin'],
    'from the console' => [null, 'console'],
]);

it('writes nothing when the host turned the activity log off', function (): void {
    config(['provisioning.activity_log' => false]);
    Notification::fake();

    app(SendInvite::class)->handle(auditedUser(), null);

    expect(Activity::query()->count())->toBe(0);
});
