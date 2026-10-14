<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-account language preference for the authenticated app (the
     * marketing page's own ro/en/de already works independently of this —
     * see MarketingController). Not RLS/tenant-scoped data, just a display
     * preference, so no BelongsToOrganization concerns here.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('locale', ['ro', 'en', 'de'])->default('ro')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
