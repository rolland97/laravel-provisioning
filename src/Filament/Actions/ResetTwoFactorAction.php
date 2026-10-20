<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Actions\ResetTwoFactor;
use Rolland\Provisioning\Support\Columns;

/**
 * Reset a user's two-factor enrolment from their page — the same action the console
 * escape hatch calls. Offered only when the account is enrolled.
 */
final class ResetTwoFactorAction
{
    public static function make(string $name = 'resetTwoFactor'): Action
    {
        return Action::make($name)
            ->label(__('provisioning::provisioning.two_factor.reset_action'))
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('provisioning::provisioning.two_factor.reset_confirm'))
            ->visible(fn (?Model $record): bool => $record !== null && filled($record->getAttribute(Columns::twoFactorSecret())))
            ->action(function (Model $record): void {
                if (! $record instanceof Authenticatable) {
                    return;
                }

                app(ResetTwoFactor::class)->handle($record, auth()->user());

                Notification::make()->title(__('provisioning::provisioning.two_factor.reset_success'))->success()->send();
            });
    }
}
