<?php

namespace App\Support\Tenancy;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

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

        try {
            return $callback();
        } finally {
            [self::$organizationId, self::$franchisePartnerId, self::$isSystemOperator, self::$resolved] = $previous;
        }
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
