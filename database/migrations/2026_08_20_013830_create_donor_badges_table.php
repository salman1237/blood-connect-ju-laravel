<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donor_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->timestamp('earned_at');

            $table->unique(['donor_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_badges');
    }
};
