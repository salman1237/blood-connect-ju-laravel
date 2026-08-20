<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Runs automatically as part of the same `migrate --force` deploy step that
 * adds the gender/batch columns — hasCompletedOnboarding() now requires
 * both, and without this, every pre-existing account (real and demo) would
 * be bounced to onboarding the moment this deploy lands. 'other' is used as
 * the gender placeholder rather than guessing male/female for real accounts
 * that never stated one; both fields remain freely editable from the
 * profile page afterward.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('gender')->update(['gender' => 'other']);

        $currentYear = (int) now()->format('Y');
        $placeholderBatch = "{$currentYear}-".substr((string) ($currentYear + 1), 2, 2);

        DB::table('users')
            ->where('role', 'student')
            ->whereNull('batch')
            ->update(['batch' => $placeholderBatch]);
    }

    public function down(): void
    {
        // Best-effort only — cannot distinguish a backfilled placeholder
        // from a value a user has since legitimately confirmed as correct.
    }
};
