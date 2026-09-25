<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->unsignedSmallInteger('min_height_cm')->nullable();
            $table->unsignedSmallInteger('max_height_cm')->nullable();
            $table->json('marital_statuses')->nullable();
            $table->json('religions')->nullable();
            $table->json('communities')->nullable();
            $table->json('mother_tongues')->nullable();
            $table->json('countries')->nullable();
            $table->json('cities')->nullable();
            $table->json('education_preferences')->nullable();
            $table->json('occupation_preferences')->nullable();
            $table->boolean('open_to_relocation')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_preferences');
    }
};
