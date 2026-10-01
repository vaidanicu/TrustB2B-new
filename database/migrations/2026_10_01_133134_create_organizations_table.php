<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Kundenorganisation (AP05/AP07): a paying B2B customer, set up and
     * supported by exactly one franchise partner. This is the tenant
     * boundary for everything beneath it (users, events, participants,
     * documents, media, data-subject requests) — see BelongsToOrganization.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_partner_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('country', 2);
            $table->json('languages')->nullable();
            $table->string('data_protection_contact_email')->nullable();
            $table->enum('contract_status', ['onboarding', 'active', 'suspended'])->default('onboarding');
            $table->unsignedInteger('storage_quota_mb')->default(5000);
            $table->unsignedInteger('event_quota')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
