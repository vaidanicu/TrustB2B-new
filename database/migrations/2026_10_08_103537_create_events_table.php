<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AP07/08 — the first content table to actually use
     * App\Models\Concerns\BelongsToOrganization (events belong to exactly
     * one customer organization; organization_id/franchise_partner_id are
     * auto-stamped on create, same as every table under it from here on).
     *
     * The "responsible roles" fields (organizer..final_package_recipient)
     * are the role assistant from roadmap §2.2 — stored as plain text for
     * now, not references to real User/Organization rows. Normalizing
     * those is a reasonable next step once there's more than one event to
     * see the pattern on, not before.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('franchise_partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->string('name');
            $table->text('purpose')->nullable();
            $table->string('country', 2);
            $table->string('language', 5);
            $table->date('starts_at');
            $table->date('ends_at');

            $table->unsignedSmallInteger('retention_days')->default(90);
            $table->enum('end_of_cycle_action', ['delete', 'export'])->default('delete');
            $table->enum('status', ['draft', 'active', 'locked', 'archived'])->default('draft');

            $table->string('organizer')->nullable();
            $table->string('co_organizer')->nullable();
            $table->string('location')->nullable();
            $table->string('agency')->nullable();
            $table->string('photographer')->nullable();
            $table->string('data_protection_contact')->nullable();
            $table->string('final_package_recipient')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
