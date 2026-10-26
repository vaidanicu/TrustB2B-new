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
            $table->timestamp('gdpr_accepted_at')->nullable()->after('photo_consent');
            $table->string('gdpr_accepted_ip')->nullable()->after('gdpr_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['gdpr_accepted_at', 'gdpr_accepted_ip']);
        });
    }
};
