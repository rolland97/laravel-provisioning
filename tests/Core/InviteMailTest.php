<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Rolland\Provisioning\Notifications\ProvisioningInvite;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * Ported from the source application's ProvisioningMailRenderTest (FR-003, FR-014).
 * Without Filament the host names its own accept route `provisioning.accept`; the
 * plugin registers it otherwise.
 */

beforeEach(function (): void {
    Route::get('accept-invite/{token}', fn () => 'ok')->name('provisioning.accept');
    config(['app.name' => 'Procurement']);
    $this->user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123']);
});

it('renders the invite with a signed set-password link and the configured lifetime', function (): void {
    $mail = (new ProvisioningInvite('fake-token-123'))->toMail($this->user);
    $html = view($mail->view, $mail->viewData)->render();

    expect($mail->subject)->toBe(__('provisioning::provisioning.invite.subject', ['app' => 'Procurement']))
        ->and($html)->toContain('accept-invite/fake-token-123')
        ->and($html)->toContain(__('provisioning::provisioning.invite.expiry', ['days' => 7]))
        ->and($html)->toContain('Ada');

    $url = html_entity_decode((string) $mail->viewData['actionUrl']);
    $request = Request::create($url);
    expect(URL::hasValidRelativeSignature($request))->toBeTrue();
});

it('lets the link lapse with the token', function (): void {
    $url = html_entity_decode((string) (new ProvisioningInvite('fake-token-123'))->toMail($this->user)->viewData['actionUrl']);

    $this->travel(config('provisioning.broker.expire') + 1)->minutes();

    expect(URL::hasValidRelativeSignature(Request::create($url)))->toBeFalse();
});

it('never puts a password in the message and keeps the token inside the link only', function (): void {
    $mail = (new ProvisioningInvite('fake-token-123'))->toMail($this->user);

    expect($mail->viewData)->not->toHaveKey('password')
        ->and(collect($mail->viewData)->except('actionUrl')->implode(' '))->not->toContain('fake-token-123');
});

it('renders the host\'s own view when configured, with the same data', function (): void {
    view()->addNamespace('host', __DIR__.'/../Fixtures/views');
    config(['provisioning.invite.mail_view' => 'host::invite']);

    $mail = (new ProvisioningInvite('fake-token-123'))->toMail($this->user);

    expect($mail->view)->toBe('host::invite')
        ->and(array_keys($mail->viewData))->toEqualCanonicalizing(['subject', 'greeting', 'bodyText', 'actionUrl', 'actionLabel', 'expiryText', 'email']);
});
