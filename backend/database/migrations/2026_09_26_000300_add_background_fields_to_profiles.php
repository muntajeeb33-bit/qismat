<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('denomination', 100)->nullable()->index()->after('religion');
            $table->string('sub_community', 120)->nullable()->index()->after('community');
            $table->string('ethnicity', 120)->nullable()->index()->after('sub_community');
        });

        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->json('denominations')->nullable()->after('religions');
            $table->json('sub_communities')->nullable()->after('communities');
            $table->json('ethnicities')->nullable()->after('sub_communities');
        });
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->dropColumn(['denominations', 'sub_communities', 'ethnicities']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['denomination', 'sub_community', 'ethnicity']);
        });
    }
};
