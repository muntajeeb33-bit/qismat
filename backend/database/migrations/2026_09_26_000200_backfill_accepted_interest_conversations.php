<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('interests')
            ->where('status', 'accepted')
            ->orderBy('id')
            ->chunkById(500, function ($interests) {
                $now = now();
                $rows = $interests->map(fn ($interest) => [
                    'user_one_id' => min($interest->sender_id, $interest->receiver_id),
                    'user_two_id' => max($interest->sender_id, $interest->receiver_id),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                DB::table('conversations')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        // Conversations may contain member messages and are intentionally retained.
    }
};
