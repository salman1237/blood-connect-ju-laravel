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
        Schema::table('users', function (Blueprint $table) {
            // Nullable: null means "no explicit preference", falling back to
            // the session locale (guest toggle) or the app default.
            $table->string('locale', 5)->nullable()->after('is_active');
            $table->boolean('email_notifications_enabled')->default(true)->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'email_notifications_enabled']);
        });
    }
};
