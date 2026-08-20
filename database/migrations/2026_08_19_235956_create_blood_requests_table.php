<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->unsignedTinyInteger('units_needed');
            $table->string('hospital_name');
            $table->string('location')->nullable();
            $table->enum('urgency', ['critical', 'within_24h', 'planned']);
            $table->text('patient_context')->nullable();
            $table->string('contact_method');
            $table->enum('status', ['open', 'donor_found', 'fulfilled', 'expired'])->default('open');
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'urgency']);
            $table->index('blood_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
