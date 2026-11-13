<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
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

    /**
     * A card page, like signing in (002 FR-004): the person can go nowhere until they
     * finish, so the panel's sidebar and top bar are noise. It stays a `Page` — its
     * route, `getUrl()` (which the enforcing middleware redirects to) and registration
     * through `$panel->pages()` are unchanged — and takes the simple layout exactly as
     * `Filament\Pages\SimplePage` declares it. Signed in, that layout shows Filament's
     * own user menu, which is the way to sign out.
     */
    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $slug = 'force-password-change';

    protected static bool $shouldRegisterNavigation = false;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $user = $this->user();

        if ($user === null || ! data_get($user, Columns::forcePasswordChange())) {
            $this->redirect(Filament::getUrl());

            return;
        }

        // e.g. an impersonator: the change waits for the account's owner.
        if ($this->sendAwayIfBlocked($user)) {
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

    /**
     * Built the way Filament's own sign-in page builds its form — a `Form` holding the
     * form schema, with a full-width submit in its footer — so the spacing and the
     * button come from Filament, not from utilities a host's theme may never compile.
     */
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('provisioning::provisioning.force_change.submit'))
                                ->submit('save'),
                        ])->fullWidth()->key('form-actions'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $user = $this->user();

        if ($user === null) {
            $this->redirect(Filament::getUrl());

            return;
        }

        if ($this->sendAwayIfBlocked($user)) {
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

    public function hasLogo(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getLayoutData(): array
    {
        // Null lets the simple layout use the panel's card width, as on the sign-in
        // page; a `Page`'s own content width is the full-page one.
        return [
            'hasTopbar' => true,
            'maxContentWidth' => null,
            'maxWidth' => null,
        ];
    }

    public function getTitle(): string
    {
        return (string) __('provisioning::provisioning.force_change.title');
    }

    public function getSubheading(): string
    {
        return (string) __('provisioning::provisioning.force_change.helper');
    }

    private function sendAwayIfBlocked(Authenticatable $user): bool
    {
        $skip = app(DecidesEnforcementSkip::class);

        if (! $skip->blocksCompletion($user)) {
            return false;
        }

        $this->redirect($skip->blockedRedirectUrl($user) ?? Filament::getUrl());

        return true;
    }

    private function user(): (Authenticatable&Model)|null
    {
        $user = Filament::auth()->user();

        return $user instanceof Model ? $user : null;
    }
}
