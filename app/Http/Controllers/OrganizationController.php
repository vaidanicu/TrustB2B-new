<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Franchise-partner view of their own client organizations ("Clienți" in
 * the Faza 1 design canvas). TenantScope on Organization already limits
 * the query to the acting user's own franchise (or, for a system
 * operator, everything) — this controller does no filtering of its own.
 */
class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        // Organization-scoped users have their own account page for this
        // later; today's screen is built for franchise/system-level staff.
        abort_if($request->user()->organization_id !== null, 403);

        $organizations = Organization::query()
            ->orderBy('name')
            ->get(['id', 'name', 'country', 'contract_status']);

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'country' => $organization->country,
                'status' => $organization->contract_status,
            ]),
        ]);
    }
}
