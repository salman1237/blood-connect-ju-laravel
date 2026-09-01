<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One shared logo turned out to be the wrong shape -- JUCSU (funder) and
 * Badhan (maintainer) each have their own logo. Safe to just drop the old
 * column rather than migrate its data: nobody had uploaded a logo yet, it
 * was still null in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('logo_url');
            $table->string('funded_by_logo_url')->nullable()->after('funded_by');
            $table->string('maintained_by_logo_url')->nullable()->after('maintained_by');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['funded_by_logo_url', 'maintained_by_logo_url']);
            $table->string('logo_url')->nullable();
        });
    }
};
