<?php

namespace App\Support\Tenancy;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Resolves "who are we acting as, for tenant-scoping purposes" for the
 * current request/console process. Read by TenantScope (app/Models/Concerns)
 * and by SetTenantContext middleware, which mirrors it into Postgres session
 * variables so Row-Level Security sees the same boundary as Eloquent does.
 *
 * Deliberately static/per-process state, explicitly set once per HTTP
 * request by SetTenantContext. A queued job has no HTTP request to trigger
 * that middleware — any job that touches tenant-scoped models MUST call
 * TenantContext::setFromUser() itself before querying, or it will see
 * nothing (deny-by-default), never the wrong tenant's data.
 */
final class TenantContext
{
    private static ?int $organizationId = null;

    private static ?int $franchisePartnerId = null;

    private static bool $isSystemOperator = false;

    private static bool $resolved = false;

    public static function organizationId(): ?int
    {
        self::resolveFromAuthIfNeeded();

        return self::$organizationId;
    }

    public static function franchisePartnerId(): ?int
    {
        self::resolveFromAuthIfNeeded();

        return self::$franchisePartnerId;
    }

    /**
     * Platform- and franchisor-level administrators: unrestricted visibility
     * into tenancy/account records (FranchisePartner, Organization, User)
     * for system operation. This does NOT extend to customer *content*
     * (events, participants, documents, media, GDPR cases once those exist)
     * — per roadmap §2, that access must go through an explicit, justified
     * and logged action instead of a silent scope bypass.
     */
    public static function isSystemOperator(): bool
    {
        self::resolveFromAuthIfNeeded();

        return self::$isSystemOperator;
    }

    /**
     * Called once per request by SetTenantContext. Explicit, not lazy-only,
     * so a second request in the same worker process never inherits state
     * left over by a previous user.
     */
    public static function setFromUser(?Authenticatable $user): void
    {
        self::$resolved = true;
        self::$organizationId = null;
        self::$franchisePartnerId = null;
        self::$isSystemOperator = false;

        if (! $user instanceof User) {
            return;
        }

        self::$organizationId = $user->organization_id;
        self::$franchisePartnerId = $user->franchise_partner_id;
        self::$isSystemOperator = $user->hasAnyRole(['platform-admin', 'franchisor-admin']);
    }

    /**
     * For console commands, tests and queued jobs that need to act as a
     * specific user without an HTTP request having set the context.
     * Syncs to the database session too — a real Postgres connection
     * enforces RLS by the DB session var, not by this PHP state, so a
     * console write that skips the sync hits the same wall a request
     * without SetTenantContext would.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function runAs(User $user, callable $callback): mixed
    {
        $previous = [self::$organizationId, self::$franchisePartnerId, self::$isSystemOperator, self::$resolved];

        self::setFromUser($user);
        self::syncToDatabaseSession();

        try {
            return $callback();
        } finally {
            [self::$organizationId, self::$franchisePartnerId, self::$isSystemOperator, self::$resolved] = $previous;
            self::syncToDatabaseSession();
        }
    }

    /**
     * For seeders, console commands and background jobs that need
     * unrestricted write access (seeding demo data across organizations,
     * a future cron that enforces the 60/90-day retention cycle across
     * every tenant) and have no real admin user to act as. Unlike the
     * request-time system-operator bypass (platform-admin/franchisor-admin,
     * "no routine insight into customer content" per roadmap §2), a
     * console run invoked by whoever controls the server already has that
     * access at the OS level — this does not grant anything new, it just
     * lets Eloquent/RLS get out of its own way for a trusted process.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function runAsSystemOperator(callable $callback): mixed
    {
        $previous = [self::$organizationId, self::$franchisePartnerId, self::$isSystemOperator, self::$resolved];

        self::$resolved = true;
        self::$organizationId = null;
        self::$franchisePartnerId = null;
        self::$isSystemOperator = true;
        self::syncToDatabaseSession();

        try {
            return $callback();
        } finally {
            [self::$organizationId, self::$franchisePartnerId, self::$isSystemOperator, self::$resolved] = $previous;
            self::syncToDatabaseSession();
        }
    }

    /**
     * Mirrors the current PHP-level state into the three Postgres session
     * variables the enable_tenant_row_level_security migration's policies
     * read (no-op on sqlite — RLS doesn't exist there). SetTenantContext
     * middleware calls this once per HTTP request; anything running outside
     * a request (seeders, console commands, queued jobs) must call it
     * itself after changing context, via runAs()/runAsSystemOperator() or
     * directly.
     */
    public static function syncToDatabaseSession(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("select set_config('app.current_organization_id', ?, false)", [
            (string) (self::organizationId() ?? ''),
        ]);
        DB::statement("select set_config('app.current_franchise_partner_id', ?, false)", [
            (string) (self::franchisePartnerId() ?? ''),
        ]);
        DB::statement("select set_config('app.is_platform_admin', ?, false)", [
            self::isSystemOperator() ? 'true' : 'false',
        ]);
    }

    /**
     * Resets to "no context" — deny-by-default, nothing visible through
     * TenantScope. Used by tests between examples and by workers that must
     * not let one job's context leak into the next.
     */
    public static function flush(): void
    {
        self::$resolved = false;
        self::$organizationId = null;
        self::$franchisePartnerId = null;
        self::$isSystemOperator = false;
    }

    private static function resolveFromAuthIfNeeded(): void
    {
        if (self::$resolved) {
            return;
        }

        self::setFromUser(auth()->user());
    }
}
