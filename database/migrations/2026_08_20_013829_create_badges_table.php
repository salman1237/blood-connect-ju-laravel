<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description');
            $table->timestamps();
        });

        // Fixed catalog, not environment-specific seed data — inserted here
        // (rather than a DatabaseSeeder, which is Phase 9 territory) so the
        // badges a donor can earn exist in every environment migrate runs
        // in, including CI's sqlite and production, with no separate step.
        DB::table('badges')->insert([
            [
                'name' => 'First Donation',
                'slug' => 'first-donation',
                'description' => 'Completed your first confirmed donation.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '5-Time Donor',
                'slug' => 'five-time-donor',
                'description' => 'Completed 5 confirmed donations.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rare Donor',
                'slug' => 'rare-blood-type',
                'description' => 'Donated with a rare (Rh-negative) blood type.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
