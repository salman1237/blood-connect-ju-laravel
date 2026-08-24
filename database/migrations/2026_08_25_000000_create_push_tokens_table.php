<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Unique, not (user_id, token) — the same physical device can
            // only ever point at one account's inbox at a time. Registering
            // it again under a different account (a shared device, or
            // logging in as someone else) reassigns it via updateOrCreate
            // rather than leaving a stale row pointed at the old owner.
            $table->string('token')->unique();
            $table->string('device_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
