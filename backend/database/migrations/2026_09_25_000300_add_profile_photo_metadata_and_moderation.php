<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->string('mime_type', 80)->nullable()->after('path');
            $table->unsignedInteger('width')->nullable()->after('mime_type');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('height');
            $table->string('moderation_feedback', 500)->nullable()->after('moderation_status');
            $table->foreignId('moderated_by')->nullable()->after('moderation_feedback')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->index(['user_id', 'moderation_status', 'is_primary'], 'profile_photos_access_idx');
        });
    }

    public function down(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropIndex('profile_photos_access_idx');
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['mime_type', 'width', 'height', 'size_bytes', 'moderation_feedback', 'moderated_at']);
        });
    }
};
