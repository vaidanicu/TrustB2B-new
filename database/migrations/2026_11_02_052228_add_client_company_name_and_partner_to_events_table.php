<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * client_company_name: client_contact_name was being used for both "the
     * company" and "the person to contact" at once, so the billing address
     * had no company of its own to actually belong to — split out
     * explicitly, client_contact_name is now only ever a person's name.
     *
     * partner_id: which regional Partner (see create_partners_table) is
     * the sales-responsible party for this specific event.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('client_company_name')->nullable()->after('client_contact_name');
            $table->foreignId('partner_id')->nullable()->after('franchise_partner_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn('client_company_name');
        });
    }
};
