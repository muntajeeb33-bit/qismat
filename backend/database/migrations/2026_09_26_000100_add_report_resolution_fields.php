<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('resolution_action', 30)->nullable()->after('status');
            $table->text('resolution_notes')->nullable()->after('resolution_action');
            $table->index(['reported_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['reported_user_id', 'status']);
            $table->dropColumn(['resolution_action', 'resolution_notes']);
        });
    }
};
