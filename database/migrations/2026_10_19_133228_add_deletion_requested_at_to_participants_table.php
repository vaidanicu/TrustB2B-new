<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The GDPR right to erasure, approval-queue version (chosen deliberately
     * over immediate self-service deletion): a participant marks their own
     * row via their signed link, then the organization's admin reviews and
     * either deletes the participant for real (ParticipantController's
     * existing destroy()) or clears the flag. No separate "GDPR case" table
     * — a deletion request only ever concerns exactly one participant row,
     * and "approve" already has a real action to call (destroy()).
     */
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('deletion_requested_at');
        });
    }
};
