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
        Schema::table('request_responses', function (Blueprint $table) {
            $table->timestamp('requester_confirmed_at')->nullable()->after('status');
            $table->timestamp('donor_confirmed_at')->nullable()->after('requester_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_responses', function (Blueprint $table) {
            $table->dropColumn(['requester_confirmed_at', 'donor_confirmed_at']);
        });
    }
};
