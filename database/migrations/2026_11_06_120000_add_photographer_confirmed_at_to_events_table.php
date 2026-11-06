<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reported: a partner/admin has no way to tell, before the event, that
     * the designated photographer actually saw their access instructions —
     * "email opened" can't be trusted (many clients block remote images,
     * Gmail proxies them regardless of whether a human read anything), so
     * this tracks something real instead: the first time that exact
     * account opens THIS event's own gallery page
     * (GalleryController::index()) while still its designated photographer.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('photographer_confirmed_at')->nullable()->after('photographer_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('photographer_confirmed_at');
        });
    }
};
