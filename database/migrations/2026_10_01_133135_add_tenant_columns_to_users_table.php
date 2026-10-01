<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A user belongs to at most one scope in the tenancy hierarchy (AP05):
     * - both null            -> platform administrator (Plattformadministrator)
     * - franchise_partner_id set, organization_id null -> franchise-level staff
     * - organization_id set  -> customer-level staff (Kundenadministrator,
     *                           Eventmanager, Datenschutzverantwortlicher,
     *                           Fotograf, Teilnehmer)
     * franchise_partner_id is also set (denormalized) for organization-scoped
     * users, so every tenant-filtered query and RLS policy can filter without
     * an extra join back through organizations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('franchise_partner_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->after('franchise_partner_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('franchise_partner_id');
        });
    }
};
