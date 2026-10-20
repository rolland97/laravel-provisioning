<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Rolland\Provisioning\Actions\CompleteForcedPasswordChange;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Contracts\ProvidesPasswordRules;
use Rolland\Provisioning\Support\Columns;

/**
 * Where an armed user is sent. The current password, a new one (the host's rules,
 * different from the current one) and its confirmation.
 *
 * @property-read Schema $form
 */
final class ForcePasswordChange extends Page
{
    protected string $view = 'provisioning::filament.pages.force-password-change';

    protected static ?string $slug = 'force-password-change';

    protected static bool $shouldRegisterNavigation = false;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $user = $this->user();

        // e.g. an impersonator: the change waits for the account's owner.
        if ($user === null || app(DecidesEnforcementSkip::class)->blocksCompletion($user) || ! data_get($user, Columns::forcePasswordChange())) {
            $this->redirect(Filament::getUrl());

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $rules = app(ProvidesPasswordRules::class);
        $requirementsView = $rules->requirementsView();

        $password = TextInput::make('password')
            ->label(__('provisioning::provisioning.force_change.new_password'))
            ->password()
            ->revealable()
            ->required()
            ->rules($rules->rules())
            ->different('current_password')
            ->validationMessages(['different' => __('provisioning::provisioning.force_change.same_as_current')])
            ->confirmed();

        if ($requirementsView !== null) {
            /** @var view-string $requirementsView */
            $password->belowContent(fn (TextInput $field): Htmlable => view($requirementsView, ['statePath' => $field->getStatePath()]));
        }

        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label(__('provisioning::provisioning.force_change.current_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword(),
                $password,
                TextInput::make('password_confirmation')
                    ->label(__('provisioning::provisioning.force_change.new_password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = $this->user();

        if ($user === null || app(DecidesEnforcementSkip::class)->blocksCompletion($user)) {
            $this->redirect(Filament::getUrl());

            return;
        }

        /** @var array{password: string} $state */
        $state = $this->form->getState();

        app(CompleteForcedPasswordChange::class)->handle($user, $state['password']);

        // Keep this session signed in across the hash change: AuthenticateSession
        // compares this key and would otherwise sign the user straight back out.
        if (app()->bound('session')) {
            session()->put(['password_hash_'.Filament::getAuthGuard() => $user->getAuthPassword()]);
        }

        Notification::make()->title(__('provisioning::provisioning.force_change.saved'))->success()->send();

        $this->redirect(Filament::getUrl());
    }

    public function getTitle(): string
    {
        return (string) __('provisioning::provisioning.force_change.title');
    }

    public function getSubheading(): string
    {
        return (string) __('provisioning::provisioning.force_change.helper');
    }

    private function user(): (Authenticatable&Model)|null
    {
        $user = Filament::auth()->user();

        return $user instanceof Model ? $user : null;
    }
}
