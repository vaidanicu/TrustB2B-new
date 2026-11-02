<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Regional resellers who represent the franchise partner in a given
     * country (e.g. HU, AT, DE) — a different concept from FranchisePartner
     * itself: a Partner is assigned per-EVENT (Event.partner_id) as whoever
     * is responsible for that sale, not a tenancy boundary of its own.
     *
     * organization_id exists only so Partner can reuse the shared
     * TenantScope: it's never actually set (Partner is always
     * franchise-level, managed by the agency, never by a client's own
     * staff), so an organization-scoped actor's TenantScope lookup
     * (WHERE organization_id = their id) always finds nothing — the same
     * deny-by-default every other tenant-scoped model gets, without
     * writing a bespoke scope just for this one column combination.
     */
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('country', 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
