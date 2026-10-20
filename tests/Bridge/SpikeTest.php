<?php

declare(strict_types=1);

use Livewire\Livewire;
use Rolland\Provisioning\Tests\Fixtures\BridgeUser;
use Rolland\Provisioning\Tests\Fixtures\Panel\SpikeMiddleware;

/*
 * T004 — prove the fragile seam before building on it (constitution, Workflow):
 * a plugin can add a page and a PERSISTENT auth middleware to the one panel that
 * registers it, and to no other.
 */

beforeEach(function (): void {
    $this->user = BridgeUser::query()->create([
        'name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'secret-password',
    ]);
});

it('serves the plugin page through the plugin middleware on the panel that registered it', function (): void {
    $this->actingAs($this->user)
        ->get('/admin/spike')
        ->assertOk()
        ->assertHeader('X-Provisioning-Spike', 'seen');
});

it('adds neither the page nor the middleware to a panel without the plugin', function (): void {
    $this->actingAs($this->user)->get('/plain/spike')->assertNotFound();

    $this->actingAs($this->user)
        ->get('/plain')
        ->assertHeaderMissing('X-Provisioning-Spike');
});

it('registers the middleware as persistent, so Livewire round trips run it too', function (): void {
    expect(Livewire::getPersistentMiddleware())->toContain(SpikeMiddleware::class);
});
