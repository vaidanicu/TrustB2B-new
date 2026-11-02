<?php

namespace App\Http\Controllers;

use App\Models\FranchisePartner;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Regional resellers (see Partner's own docblock) that the agency picks
 * from when assigning an event's sales-responsible party
 * (EventController's partner_id field) — managed here exactly like
 * Organizations: the acting actor's own franchise_partner_id scopes
 * everything (TenantScope on Partner), a system operator picks one
 * explicitly on create, a franchise-partner-admin has exactly one already.
 */
class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $partners = Partner::query()->orderBy('name')->get(['id', 'name', 'country']);

        return Inertia::render('Partners/Index', [
            'partners' => $partners,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        return Inertia::render('Partners/Create', [
            'franchisePartners' => $request->user()->franchise_partner_id !== null
                ? []
                : FranchisePartner::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'franchise_partner_id' => [
                Rule::requiredIf($request->user()->franchise_partner_id === null),
                'nullable', 'exists:franchise_partners,id',
            ],
        ]);

        $partner = Partner::create([
            'name' => $data['name'],
            'country' => strtoupper($data['country']),
            'franchise_partner_id' => $request->user()->franchise_partner_id ?? $data['franchise_partner_id'],
        ]);

        activity()->causedBy($request->user())->performedOn($partner)->log('partner created');

        return redirect()->route('partners.index');
    }
}
