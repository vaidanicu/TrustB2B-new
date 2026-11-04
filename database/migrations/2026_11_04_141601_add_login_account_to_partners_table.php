<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * A Partner was a plain lookup row with no way to log in — reported:
     * removal-request emails landed in a personal inbox with no
     * consolidated view across events. email/user_id let the agency give a
     * Partner a real account (PartnerController::store(), mirroring
     * EventController's own findOrCreatePhotographer()) so they can log in
     * and see every flagged photo across the events assigned to them in
     * one place.
     */
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('email')->nullable()->after('country');
            $table->foreignId('user_id')->nullable()->after('email')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('email');
        });
    }
};
