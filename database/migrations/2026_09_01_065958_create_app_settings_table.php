<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A singleton row -- one record ever, holding the org credit shown on the
 * landing page and Settings ("Implemented & funded by...", "Maintained
 * by..."), admin-editable via App\Models\AppSetting::current(). Seeded here
 * with the real values so production is correct immediately after this
 * migration runs, without waiting on an admin to log in first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('funded_by')->nullable();
            $table->string('maintained_by')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            'funded_by' => "Jahangirnagar University Central Students' Union (JUCSU)",
            'maintained_by' => 'Badhan, Jahangirnagar University',
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
