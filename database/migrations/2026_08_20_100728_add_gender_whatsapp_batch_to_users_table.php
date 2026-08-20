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
            // Nullable at the DB level so existing users aren't broken by
            // this migration — required going forward via onboarding/
            // profile-edit validation instead.
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('role');
            $table->string('batch', 10)->nullable()->after('department');
            $table->boolean('phone_has_whatsapp')->default(true)->after('phone');
            $table->string('whatsapp_number', 30)->nullable()->after('phone_has_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'batch', 'phone_has_whatsapp', 'whatsapp_number']);
        });
    }
};
