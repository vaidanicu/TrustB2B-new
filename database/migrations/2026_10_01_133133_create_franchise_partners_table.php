<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The franchise partner is the top level of the TrustBridge tenancy
     * hierarchy (AP05): TRUSTBRIDGE -> FranchisePartner -> Organization.
     * Each partner is a legally independent company (e.g. RO-Auras,
     * HU-MM, DE-2-3 Partner) that onboards and supports its own customers.
     */
    public function up(): void
    {
        Schema::create('franchise_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region_code')->unique();
            $table->string('country', 2);
            $table->string('contact_email');
            $table->enum('contract_status', ['onboarding', 'active', 'suspended'])->default('onboarding');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_partners');
    }
};
