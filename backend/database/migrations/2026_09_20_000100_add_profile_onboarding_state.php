<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('display_name', 120)->nullable();
            $table->string('moderation_status', 30)->default('draft')->index();
            $table->boolean('discovery_opt_in')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
        // Existing profiles were not approved under the new process and remain hidden.
        Schema::table('profiles', function (Blueprint $table) {
            $table->index(['moderation_status', 'discovery_opt_in', 'visibility'], 'profiles_discovery_idx');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_discovery_idx');
            $table->dropColumn(['display_name', 'moderation_status', 'discovery_opt_in', 'submitted_at', 'approved_at']);
        });
    }
};
