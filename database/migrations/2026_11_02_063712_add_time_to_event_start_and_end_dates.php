<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * starts_at/ends_at were date-only — an organizer had no way to tell
     * participants (or UpdateEventStatusesCommand) what TIME an event
     * actually starts, only which day. Widened to a real timestamp.
     *
     * Raw ALTER rather than Blueprint::change(): this app doesn't carry
     * doctrine/dbal (CLAUDE.md: no new dependencies without approval),
     * which Blueprint::change() needs to introspect the existing column.
     * SQLite has no real column types — a "date" column already stores
     * whatever string is written to it, full datetime included — so
     * there's nothing to alter there; only Postgres needs the statement.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE events ALTER COLUMN starts_at TYPE timestamp');
            DB::statement('ALTER TABLE events ALTER COLUMN ends_at TYPE timestamp');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE events ALTER COLUMN starts_at TYPE date');
            DB::statement('ALTER TABLE events ALTER COLUMN ends_at TYPE date');
        }
    }
};
