<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * trustbridgeb2b.com and app.trustbridgeb2b.com point at the same codebase
 * and document root (see MarketingController's docblock) — but that
 * controller only ever gates the "/" route. Every other route (dashboard,
 * events, participants, gallery...) had no domain check at all, so it
 * worked identically on either host. That let an organizer end up
 * authenticated on the bare marketing domain and generate a participant
 * invitation link signed against the WRONG host — Laravel's signed-URL
 * validation includes the host, so the link 403'd for anyone who clicked
 * it. Redirecting here, before any such link can be generated, is the
 * actual fix; MarketingController's own redirect only ever covered "/".
 */
class EnsureAppDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($appHost !== null && $request->getHost() !== $appHost) {
            $target = preg_replace('/^https?:\/\/[^\/]+/', config('app.url'), $request->fullUrl());

            return redirect()->to($target);
        }

        return $next($request);
    }
}
