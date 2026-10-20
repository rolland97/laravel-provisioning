<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Defaults;

use Illuminate\Validation\Rules\Password;
use Rolland\Provisioning\Contracts\ProvidesPasswordRules;

/** Whatever the application set as `Password::defaults()`, and no requirements view. */
final class LaravelPasswordRules implements ProvidesPasswordRules
{
    public function rules(): array
    {
        return [Password::defaults()];
    }

    public function requirementsView(): ?string
    {
        return null;
    }
}
