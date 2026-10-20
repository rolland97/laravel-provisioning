<?php

declare(strict_types=1);

use Rolland\Provisioning\Tests\BridgeTestCase;
use Rolland\Provisioning\Tests\BrowserTestCase;
use Rolland\Provisioning\Tests\TestCase;

// ⚠️ `Core` gets the Filament-free base class, and nothing else does: the core suite
// runs in CI with filament/filament UNINSTALLED (AGENTS.md R-022).
uses(TestCase::class)->in('Core');
uses(BridgeTestCase::class)->in('Bridge');
uses(BrowserTestCase::class)->in('Browser');
