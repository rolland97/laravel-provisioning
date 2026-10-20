<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Events;

/**
 * ⚠️ Identifiers only — never a model, never the token (constitution II).
 */
final readonly class InviteResent
{
    public function __construct(
        public int|string $userId,
        public int|string|null $causerId,
    ) {}
}
