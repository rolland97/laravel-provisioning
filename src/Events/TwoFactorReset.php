<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Events;

/**
 * ⚠️ Identifiers only — never a model, never the secret or the recovery codes
 * (constitution II). `via` is `admin` or `console`.
 */
final readonly class TwoFactorReset
{
    public const string VIA_ADMIN = 'admin';

    public const string VIA_CONSOLE = 'console';

    public function __construct(
        public int|string $userId,
        public int|string|null $causerId,
        public string $via,
    ) {}
}
