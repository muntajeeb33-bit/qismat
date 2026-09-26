<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->index('country');
            $table->index('height_cm');
        });
        Schema::table('profile_views', function (Blueprint $table) {
            $table->index(['viewer_id', 'viewed_user_id', 'viewed_at'], 'profile_views_daily_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('profile_views', fn (Blueprint $table) => $table->dropIndex('profile_views_daily_lookup'));
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropIndex(['height_cm']);
        });
    }
};
