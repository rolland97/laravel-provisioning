<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rolland\Provisioning\Notifications\ProvisioningInvite;
use Rolland\Provisioning\Tests\Fixtures\User;

/*
 * FR-005, constitution II: a queued notification is serialised into the queue's
 * storage, where a queue dashboard shows it. The source application's invite carried
 * the token there in clear; the package's is encrypted.
 */

it('does not put the token in clear in the queued payload', function (): void {
    Schema::create('jobs', function (Blueprint $table): void {
        $table->id();
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });
    config(['queue.default' => 'database', 'queue.connections.database.table' => 'jobs']);

    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'whatever-123']);

    $user->notify(new ProvisioningInvite('the-secret-invite-token-0123456789'));

    $payload = (string) DB::table('jobs')->value('payload');

    expect($payload)->not->toBe('', 'Nothing was queued, so this test proves nothing.')
        ->and($payload)->not->toContain('the-secret-invite-token-0123456789');
});
