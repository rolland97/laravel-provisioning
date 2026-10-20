<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests;

/**
 * The bridge's two panels, served to a real browser. Filament's own assets are
 * published so the pages render styled — a test against an unstyled page passes
 * accessibility checks it would fail in a host.
 */
abstract class BrowserTestCase extends BridgeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('filament:assets')->run();
    }
}
