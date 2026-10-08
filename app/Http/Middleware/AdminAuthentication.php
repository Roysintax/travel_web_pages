<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthentication
{
    public static function allowsLocalAccess(Request $request): bool
    {
        return ! config('cms.auth_enabled', true) && app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true) && in_array($request->getHost(), ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (self::allowsLocalAccess($request)) {
            return $next($request);
        }

        return app(Authenticate::class)->handle($request, fn (Request $request): Response => app(Authorize::class)->handle($request, $next, 'access-admin'));
    }
}
