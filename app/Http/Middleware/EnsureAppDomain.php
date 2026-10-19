<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
 *
 * A plain redirect() breaks when the triggering request was an Inertia
 * XHR visit (e.g. the marketing page's own "Autentificare" link, before
 * those were changed to plain <a> tags): the browser blocks a cross-origin
 * redirect on an XHR/fetch via CORS, since app.trustbridgeb2b.com sends no
 * Access-Control-Allow-Origin for trustbridgeb2b.com (correctly — this app
 * was never meant to take cross-origin requests). Inertia::location()
 * instead sends back a 409 with X-Inertia-Location, which Inertia's own
 * client recognizes and turns into a real window.location navigation —
 * not an XHR follow — sidestepping CORS entirely. Belt-and-suspenders
 * with the plain-<a> fix: this covers any other Inertia visit that still
 * ends up on the wrong domain, not just the three known links.
 */
class EnsureAppDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($appHost !== null && $request->getHost() !== $appHost) {
            $target = preg_replace('/^https?:\/\/[^\/]+/', config('app.url'), $request->fullUrl());

            return $request->header('X-Inertia')
                ? Inertia::location($target)
                : redirect()->to($target);
        }

        return $next($request);
    }
}
