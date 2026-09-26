<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('email_new_interest')->default(true);
            $table->boolean('email_interest_accepted')->default(true);
            $table->boolean('email_new_message')->default(true);
            $table->boolean('email_moderation_updates')->default(true);
            $table->boolean('email_product_updates')->default(false);
            $table->boolean('push_new_interest')->default(true);
            $table->boolean('push_interest_accepted')->default(true);
            $table->boolean('push_new_message')->default(true);
            $table->boolean('push_moderation_updates')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
