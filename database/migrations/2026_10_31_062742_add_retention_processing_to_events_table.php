<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the daily events:process-retention command. retention_processed_at
     * marks an event as already handled (delete or export), so the command
     * never reprocesses it. export_path/export_password only exist between
     * "export built and emailed" and "recipient confirmed receipt" — both
     * are cleared the moment the originals are deleted, see
     * EventExportController::confirm().
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('retention_processed_at')->nullable();
            $table->string('export_path')->nullable();
            $table->string('export_password')->nullable();
            $table->timestamp('export_confirmed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['retention_processed_at', 'export_path', 'export_password', 'export_confirmed_at']);
        });
    }
};
