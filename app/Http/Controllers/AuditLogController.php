<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Roadmap §9.1 / AP21: who did what, when. Backed by spatie/activitylog
 * (AssignRole already writes to it — see app/Actions/Tenancy/AssignRole).
 *
 * Activity has no organization_id/franchise_partner_id column of its own,
 * so — unlike Organization — this cannot lean on TenantScope. It is scoped
 * here by the *causer's* tenancy instead: an organization-scoped actor
 * sees entries caused by someone in their organization, a franchise-scoped
 * actor sees entries caused by anyone in their franchise, a system
 * operator sees everything. An action with no recorded causer (a console
 * command, a seeder) is only visible to a system operator — never shown as
 * if it belonged to a tenant it cannot be attributed to.
 *
 * Known gap: this will not surface activity whose causer is NOT the
 * tenant-scoped actor but whose *subject* is (e.g. a platform admin
 * acting on a customer's record) — the roadmap's own data model has a
 * dedicated Auditereignis entity with its own tenant columns for exactly
 * this; revisit once that exists instead of approximating further here.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $activities = Activity::query()
            ->with('causer')
            ->when($user->organization_id !== null, fn ($query) => $query->whereHasMorph(
                'causer',
                [User::class],
                fn ($q) => $q->where('organization_id', $user->organization_id),
            ))
            ->when($user->organization_id === null && $user->franchise_partner_id !== null, fn ($query) => $query->whereHasMorph(
                'causer',
                [User::class],
                fn ($q) => $q->where('franchise_partner_id', $user->franchise_partner_id),
            ))
            ->latest()
            ->limit(100)
            ->get();

        return Inertia::render('AuditLog/Index', [
            'entries' => $activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'causer' => $activity->causer?->name ?? 'Sistem',
                'subject' => class_basename($activity->subject_type ?? '').($activity->subject?->name ? ": {$activity->subject->name}" : ''),
                'role' => $activity->properties['role'] ?? null,
                'at' => $activity->created_at->toIso8601String(),
            ]),
        ]);
    }
}
