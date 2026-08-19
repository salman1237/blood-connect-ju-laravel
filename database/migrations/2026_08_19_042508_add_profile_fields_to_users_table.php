<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'staff', 'faculty', 'verifier', 'admin'])
                ->default('student')
                ->after('email');
            $table->string('hall')->nullable()->after('role');
            $table->string('department')->nullable()->after('hall');
            $table->string('phone')->nullable()->after('department');
            $table->string('google_id')->nullable()->unique()->after('phone');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'hall', 'department', 'phone', 'google_id']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
