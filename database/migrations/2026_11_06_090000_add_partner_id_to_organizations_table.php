<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which Partner (see create_partners_table) onboarded this client —
     * reported: a Partner should be able to create and administer its own
     * clients directly, not only create events for clients a franchise/
     * platform actor already set up. nullOnDelete() mirrors events.partner_id:
     * deleting a Partner never takes its clients down with it, just the
     * attribution.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('franchise_partner_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
        });
    }
};
