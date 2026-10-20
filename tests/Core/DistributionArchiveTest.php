<?php

declare(strict_types=1);

/*
 * Constitution VI, FR-020, AGENTS R-027: what a host downloads is the runtime only.
 * Reads the archive git would build from the committed tree, so an uncommitted
 * .gitattributes change cannot make it pass.
 */

it('ships the runtime and nothing else', function (): void {
    $root = dirname(__DIR__, 2);

    if (! is_dir($root.'/.git')) {
        $this->markTestSkipped('Not a git checkout.');
    }

    exec('git -C '.escapeshellarg($root).' archive --format=tar HEAD | tar -t', $files, $status);

    expect($status)->toBe(0);

    $topLevel = collect($files)->map(fn (string $file): string => explode('/', $file)[0])->unique()->sort()->values()->all();

    expect($topLevel)->toBe(['LICENSE', 'README.md', 'composer.json', 'config', 'database', 'lang', 'resources', 'src']);
});
