<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Rolland\Provisioning\Actions\SendInvite;
use Rolland\Provisioning\Events\InviteResent;
use Rolland\Provisioning\Events\InviteSent;
use Rolland\Provisioning\Notifications\ProvisioningInvite;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported from the source application's ProvisionUserTest (US1, FR-001, FR-002,
 * FR-006). Audit there was an activity-log entry; here it is a domain event, and the
 * optional listener turns it back into the same entry (ActivityLogListenerTest).
 */

function inviteUser(array $attributes = []): User
{
    return User::query()->create([
        'name' => 'Ada',
        'email' => 'ada@example.test',
        'password' => 'an-unusable-placeholder-'.bin2hex(random_bytes(8)),
        ...$attributes,
    ]);
}

it('creates an invite token in its own table and queues a mail-only invite', function (): void {
    Notification::fake();
    Event::fake([InviteSent::class]);

    $user = inviteUser();
    $admin = inviteUser(['email' => 'admin@example.test']);

    app(SendInvite::class)->handle($user, $admin);

    Notification::assertSentTo(
        $user,
        ProvisioningInvite::class,
        fn (ProvisioningInvite $notification, array $channels): bool => $channels === ['mail'],
    );

    $this->assertDatabaseHas('provisioning_tokens', ['email' => $user->email]);
    // 🚨 And NOT in the password-reset table — asserting only the new location would
    // still pass if the two were merged back (source: 089).
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

    Event::assertDispatched(InviteSent::class, fn (InviteSent $event): bool => $event->userId === $user->getKey()
        && $event->causerId === $admin->getKey());
});

it('invalidates the previous invite when it is re-sent', function (): void {
    Notification::fake();
    Event::fake([InviteResent::class]);

    $user = inviteUser();

    app(SendInvite::class)->handle($user, null);

    $firstToken = null;
    Notification::assertSentTo($user, ProvisioningInvite::class, function (ProvisioningInvite $notification) use (&$firstToken): bool {
        $firstToken = $notification->token;

        return true;
    });

    expect(Password::broker('provisioning')->tokenExists($user, $firstToken))->toBeTrue();

    app(SendInvite::class)->handle($user, null, resend: true);

    expect(Password::broker('provisioning')->tokenExists($user, $firstToken))->toBeFalse();
    Event::assertDispatched(InviteResent::class, fn (InviteResent $event): bool => $event->userId === $user->getKey()
        && $event->causerId === null);
});

it('never arms the forced change for an invited person', function (): void {
    Notification::fake();

    $user = inviteUser(['force_password_change' => false]);

    app(SendInvite::class)->handle($user, null);

    expect($user->refresh()->force_password_change)->toBeFalse();
});

it('is queued, so creating an account never waits on mail', function (): void {
    expect(new ProvisioningInvite('token'))
        ->toBeInstanceOf(ShouldQueue::class)
        ->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and((new ProvisioningInvite('token'))->via(inviteUser()))->toBe(['mail']);
});
