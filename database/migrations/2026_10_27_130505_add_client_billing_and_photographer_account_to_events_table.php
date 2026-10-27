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
        Schema::table('events', function (Blueprint $table) {
            $table->string('client_contact_name')->nullable()->after('data_protection_contact');
            $table->string('client_contact_email')->nullable()->after('client_contact_name');
            $table->string('client_address')->nullable()->after('client_contact_email');
            $table->string('client_tax_number')->nullable()->after('client_address');
            $table->foreignId('photographer_user_id')->nullable()->after('photographer')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photographer_user_id');
            $table->dropColumn(['client_contact_name', 'client_contact_email', 'client_address', 'client_tax_number']);
        });
    }
};
