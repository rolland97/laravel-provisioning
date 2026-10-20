<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Events;

/**
 * ⚠️ Identifiers only — never a model, never the password (constitution II).
 */
final readonly class ForcedPasswordChangeCompleted
{
    public function __construct(
        public int|string $userId,
    ) {}
}
