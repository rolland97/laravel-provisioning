<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Contracts;

/**
 * The password rules for the invite and forced-change pages.
 */
interface ProvidesPasswordRules
{
    /**
     * Validation rules for a new password, without `required` or `confirmed`.
     *
     * @return array<int, mixed>
     */
    public function rules(): array;

    /**
     * A view rendered beneath the password field, or null for none. It receives
     * `statePath` (the field's state path).
     */
    public function requirementsView(): ?string;
}
