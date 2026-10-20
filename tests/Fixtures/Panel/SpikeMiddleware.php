<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Tests\Fixtures\Panel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** T004 spike: marks every response that went through it. */
final class SpikeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Provisioning-Spike', 'seen');

        return $response;
    }
}
