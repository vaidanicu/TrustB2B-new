<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AP10-13. Same BelongsToOrganization pattern as events: organization_id
     * and franchise_partner_id are denormalized and auto-stamped, not derived
     * at query time through event_id, so TenantScope/RLS can filter this
     * table directly without a join.
     *
     * The status enum carries all eight A-05 groups up front (roadmap §3)
     * even though this pass only ever writes invited/opened/attending/
     * declined — delivered/undeliverable need a real email pipeline and
     * no_response/withdrawn need a scheduled sweep, neither of which exists
     * yet. Having the column shapes now avoids an enum migration later.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('franchise_partner_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('email');
            $table->enum('status', [
                'invited', 'delivered', 'opened', 'attending',
                'declined', 'no_response', 'undeliverable', 'withdrawn',
            ])->default('invited');
            $table->boolean('photo_consent')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('link_expires_at');

            $table->timestamps();

            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
