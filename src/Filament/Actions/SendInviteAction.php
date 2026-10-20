<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Actions\SendInvite;
use Rolland\Provisioning\Support\Columns;

/**
 * Send (or re-send) the invite from a user's page. Offered only to someone who has
 * never signed in — after that a password exists that the person chose.
 */
final class SendInviteAction
{
    public static function make(string $name = 'sendProvisioningInvite'): Action
    {
        return Action::make($name)
            ->label(__('provisioning::provisioning.invite.resend_action'))
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $record !== null && blank($record->getAttribute(Columns::lastLoginAt())))
            ->action(function (Model $record): void {
                if (! $record instanceof Authenticatable || ! $record instanceof CanResetPassword) {
                    return;
                }

                $causer = auth()->user();
                app(SendInvite::class)->handle($record, $causer, resend: true);

                Notification::make()->title(__('provisioning::provisioning.invite.sent'))->success()->send();
            });
    }
}
