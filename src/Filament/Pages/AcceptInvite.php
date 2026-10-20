<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Rolland\Provisioning\Actions\AcceptInvite as AcceptInviteAction;
use Rolland\Provisioning\Contracts\ProvidesPasswordRules;

/**
 * The page an invite link opens: a guest page in the accepting panel's simple layout.
 * The signature on the link is checked by the route; the token by the broker.
 *
 * @property-read Schema $form
 */
final class AcceptInvite extends SimplePage
{
    protected string $view = 'provisioning::filament.pages.accept-invite';

    // Fixed by the signed link at mount; never client-mutable.
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(string $token, ?string $email = null): void
    {
        $this->token = $token;
        $this->email = $email ?? (string) request()->query('email', '');

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $rules = app(ProvidesPasswordRules::class);
        $requirementsView = $rules->requirementsView();

        $password = TextInput::make('password')
            ->label(__('provisioning::provisioning.invite.new_password'))
            ->password()
            ->revealable()
            ->required()
            ->autocomplete('new-password')
            ->rules($rules->rules());

        if ($requirementsView !== null) {
            /** @var view-string $requirementsView */
            $password->belowContent(fn (TextInput $field): Htmlable => view($requirementsView, ['statePath' => $field->getStatePath()]));
        }

        return $schema
            ->components([
                $password,
                TextInput::make('passwordConfirmation')
                    ->label(__('provisioning::provisioning.invite.confirm_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->autocomplete('new-password')
                    ->same('password'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        /** @var array{password: string} $state */
        $state = $this->form->getState();

        if (! app(AcceptInviteAction::class)->handle($this->email, $this->token, $state['password'])) {
            throw ValidationException::withMessages([
                'data.password' => __('provisioning::provisioning.invite.invalid'),
            ]);
        }

        $this->redirect(Filament::getCurrentOrDefaultPanel()?->getLoginUrl() ?? '/');
    }

    public function getTitle(): string
    {
        return (string) __('provisioning::provisioning.invite.heading');
    }

    public function getSubheading(): string
    {
        return (string) __('provisioning::provisioning.invite.intro', ['app' => config('app.name')]);
    }
}
