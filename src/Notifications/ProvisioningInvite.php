<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Rolland\Provisioning\Support\InviteUrl;
use SensitiveParameter;

/**
 * The invite. Queued, so creating an account never waits on mail (constitution IV);
 * encrypted on the queue, because the token is a property of this object and a queue
 * dashboard shows payloads (constitution II, FR-005).
 *
 * The view receives exactly: subject, greeting, bodyText, actionUrl, actionLabel,
 * expiryText, email — a host restyles by pointing `provisioning.invite.mail_view` at
 * its own view with the same variables.
 */
final class ProvisioningInvite extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(#[SensitiveParameter] public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CanResetPassword $notifiable): MailMessage
    {
        $app = (string) config('app.name');
        $subject = __('provisioning::provisioning.invite.subject', ['app' => $app]);
        $days = max(1, intdiv((int) config('provisioning.broker.expire', 10080), 1440));

        /** @var view-string $view */
        $view = (string) config('provisioning.invite.mail_view', 'provisioning::mail.invite');

        return (new MailMessage)
            ->subject($subject)
            ->view($view, [
                'subject' => $subject,
                'greeting' => __('provisioning::provisioning.invite.greeting', ['name' => (string) data_get($notifiable, 'name')]),
                'bodyText' => __('provisioning::provisioning.invite.body', ['app' => $app]),
                'actionUrl' => InviteUrl::for($notifiable, $this->token),
                'actionLabel' => __('provisioning::provisioning.invite.action'),
                'expiryText' => __('provisioning::provisioning.invite.expiry', ['days' => $days]),
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
    }
}
