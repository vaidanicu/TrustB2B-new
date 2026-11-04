<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds 'finished' between 'active' and 'archived' — an event whose own
     * end date has passed (UpdateEventStatusesCommand) now reads as done,
     * rather than staying "active" for the whole photo-retention window
     * until it finally jumps to 'archived'.
     *
     * Raw ALTER rather than Blueprint::change(): this app doesn't carry
     * doctrine/dbal (CLAUDE.md: no new dependencies without approval),
     * which Blueprint::change() needs to introspect the existing column.
     * Laravel's enum() on Postgres is just a varchar(255) with an inline,
     * auto-named CHECK constraint (Postgres' own default naming for an
     * unnamed column constraint: "<table>_<column>_check") — nothing to
     * alter on SQLite, which never enforces that check to begin with.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE events DROP CONSTRAINT events_status_check');
            DB::statement("ALTER TABLE events ADD CONSTRAINT events_status_check CHECK (status IN ('draft', 'active', 'locked', 'archived', 'finished'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE events DROP CONSTRAINT events_status_check');
            DB::statement("ALTER TABLE events ADD CONSTRAINT events_status_check CHECK (status IN ('draft', 'active', 'locked', 'archived'))");
        }
    }
};
