<?php

use App\Http\Middleware\EnsureAppDomain;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTenantContext::class,
        ]);

        $middleware->alias(['app-domain' => EnsureAppDomain::class]);

        // The 'web' group's own SubstituteBindings entry (which resolves
        // any {model} route parameter, e.g. EventController's {event})
        // otherwise always runs before whatever is appended to the group —
        // including SetTenantContext above. Every tenant-scoped route
        // without a bound model parameter happened to still work (the
        // scope/model are only queried later, inside the controller, by
        // which point SetTenantContext has already run), which is why this
        // went unnoticed until a route bound a BelongsToOrganization model
        // directly (ParticipantController's {event}-bound routes): the
        // binding queried Event under whatever stale tenant context existed
        // before this request — not this request's actual user — and
        // denied it. Moving SetTenantContext ahead of SubstituteBindings in
        // the priority list (not just the group) fixes the order for real.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetTenantContext::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
