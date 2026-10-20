<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Listeners;

use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Events\InviteAccepted;
use Rolland\Provisioning\Events\InviteResent;
use Rolland\Provisioning\Events\InviteSent;
use Rolland\Provisioning\Events\TwoFactorReset;

/**
 * Writes the provisioning events to spatie/laravel-activitylog — registered only when
 * that package is installed, and silent when `provisioning.activity_log` is false.
 *
 * ⚠️ The event names are the source application's and MUST NOT change (AGENTS R-018):
 * its activity viewer and translations key on them. The entry carries the subject,
 * the causer and, for a two-factor reset, `via` — never a secret (constitution II).
 */
final class RecordProvisioningActivity
{
    public function handle(InviteSent|InviteResent|InviteAccepted|ForcedPasswordChangeCompleted|TwoFactorReset $event): void
    {
        if (! config('provisioning.activity_log', true)) {
            return;
        }

        [$name, $causerId, $properties] = match (true) {
            $event instanceof InviteSent => ['provisioning.sent', $event->causerId, []],
            $event instanceof InviteResent => ['provisioning.resent', $event->causerId, []],
            $event instanceof InviteAccepted => ['provisioning.completed', $event->userId, []],
            $event instanceof ForcedPasswordChangeCompleted => ['password.force_change_completed', $event->userId, []],
            $event instanceof TwoFactorReset => ['2fa.reset', $event->causerId, ['via' => $event->via]],
        };

        $subject = $this->user($event->userId);

        if ($subject === null) {
            return;
        }

        $entry = activity()->performedOn($subject)->event($name);

        $causer = $causerId === null ? null : $this->user($causerId);

        if ($causer !== null) {
            $entry->causedBy($causer);
        }

        if ($properties !== []) {
            $entry->withProperties($properties);
        }

        $entry->log($name);
    }

    private function user(int|string $id): ?Model
    {
        $provider = (string) config('provisioning.broker.provider', 'users');
        $model = config("auth.providers.{$provider}.model");

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            return null;
        }

        return $model::query()->find($id);
    }
}
