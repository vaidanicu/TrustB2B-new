<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('address')->nullable()->after('email');
            $table->string('company_name')->nullable()->after('address');
            $table->string('checkin_code', 12)->nullable()->after('link_expires_at');
            $table->timestamp('checked_in_at')->nullable()->after('checkin_code');

            // Unique per event, not globally — two different events landing
            // on the same short code is fine, check-in always looks a code
            // up scoped to one event anyway (ParticipantController::checkin()).
            $table->unique(['event_id', 'checkin_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['address', 'company_name', 'checkin_code', 'checked_in_at']);
        });
    }
};
