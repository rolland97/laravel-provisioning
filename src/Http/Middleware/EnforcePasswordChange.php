<?php

declare(strict_types=1);

namespace Rolland\Provisioning\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Rolland\Provisioning\Contracts\DecidesEnforcementSkip;
use Rolland\Provisioning\Support\Columns;
use Symfony\Component\HttpFoundation\Response;

/**
 * The trap: while the forced change is armed, every request goes to the change page —
 * except the routes that must stay reachable (constitution IV) and anything the host's
 * skip rule lets through. Put it after authentication.
 */
class EnforcePasswordChange
{
    public function __construct(protected readonly DecidesEnforcementSkip $enforcementSkip) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->user($request);

        if (! $user instanceof Authenticatable || ! $this->isArmed($user)) {
            return $next($request);
        }

        if ($this->enforcementSkip->shouldSkip($request, $user)) {
            return $next($request);
        }

        foreach ($this->exceptions() as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        return redirect()->to($this->changePageUrl());
    }

    protected function user(Request $request): ?Authenticatable
    {
        return $request->user();
    }

    /**
     * Route-name patterns that stay reachable while armed.
     *
     * @return array<int, string>
     */
    protected function exceptions(): array
    {
        /** @var array<int, string> $except */
        $except = (array) config('provisioning.enforcement.except', []);

        $redirect = config('provisioning.enforcement.redirect_route');

        return is_string($redirect) && $redirect !== '' ? [...$except, $redirect] : $except;
    }

    protected function changePageUrl(): string
    {
        return route((string) config('provisioning.enforcement.redirect_route'));
    }

    private function isArmed(Authenticatable $user): bool
    {
        return (bool) data_get($user, Columns::forcePasswordChange());
    }
}
