<?php

declare(strict_types=1);

use Rolland\Provisioning\Events\ForcedPasswordChangeCompleted;
use Rolland\Provisioning\Events\InviteAccepted;
use Rolland\Provisioning\Events\InviteResent;
use Rolland\Provisioning\Events\InviteSent;
use Rolland\Provisioning\Events\TwoFactorReset;

/*
 * Constitution II: an event carries identifiers and plain values only. A model on an
 * event is one naive listener away from serialising a two-factor secret into a log.
 */

it('carries no object and nothing named like a secret', function (string $event): void {
    foreach ((new ReflectionClass($event))->getProperties() as $property) {
        $type = (string) $property->getType();

        expect(preg_match('/^(\??(int|string|bool|float|null)\|?)+$/', $type))
            ->toBe(1, "{$event}::\${$property->getName()} is typed {$type}");

        expect(preg_match('/password|token|secret|code/i', $property->getName()))
            ->toBe(0, "{$event}::\${$property->getName()} is named like a secret");
    }
})->with([
    InviteSent::class,
    InviteResent::class,
    InviteAccepted::class,
    ForcedPasswordChangeCompleted::class,
    TwoFactorReset::class,
]);
