<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests\Fixtures\Panel;

use Filament\Pages\Page;

final class SpikePage extends Page
{
    protected string $view = 'provisioning-tests::spike';

    protected static ?string $slug = 'spike';
}
