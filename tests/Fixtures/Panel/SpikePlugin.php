<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests\Fixtures\Panel;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * T004 spike: the mechanism the real plugin will use — a plugin that adds a page and a
 * PERSISTENT auth middleware to the one panel that registers it.
 */
final class SpikePlugin implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'provisioning-spike';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([SpikePage::class])
            ->authMiddleware([SpikeMiddleware::class], isPersistent: true);
    }

    public function boot(Panel $panel): void {}
}
