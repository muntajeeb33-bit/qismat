<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('role', 30)->default('member')->index();
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
        });
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile_code', 30)->nullable()->unique();
            $table->string('created_by', 30)->nullable();
            $table->string('gender', 20)->nullable()->index();
            $table->date('date_of_birth')->nullable()->index();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->string('marital_status', 40)->nullable()->index();
            $table->string('religion', 80)->nullable()->index();
            $table->string('community', 100)->nullable()->index();
            $table->string('mother_tongue', 80)->nullable()->index();
            $table->string('country', 80)->nullable();
            $table->string('state', 80)->nullable()->index();
            $table->string('city', 80)->nullable()->index();
            $table->string('education', 180)->nullable();
            $table->string('occupation', 180)->nullable();
            $table->string('company', 180)->nullable();
            $table->unsignedBigInteger('annual_income')->nullable();
            $table->text('about_me')->nullable();
            $table->json('family_details')->nullable();
            $table->json('partner_expectations')->nullable();
            $table->unsignedTinyInteger('profile_completion')->default(0);
            $table->string('verification_status', 30)->default('unverified')->index();
            $table->string('visibility', 30)->default('members');
            $table->timestamp('last_active_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->string('visibility', 30)->default('members');
            $table->string('moderation_status', 30)->default('pending')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('message', 500)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['sender_id', 'receiver_id']);
        });
        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('favourite_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'favourite_user_id']);
        });
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('viewed_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['viewed_user_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
        Schema::dropIfExists('favourites');
        Schema::dropIfExists('interests');
        Schema::dropIfExists('profile_photos');
        Schema::dropIfExists('profiles');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone', 'phone_verified_at', 'role', 'status', 'last_login_at']));
    }
};
