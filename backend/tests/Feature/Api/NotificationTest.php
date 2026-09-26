<?php

namespace Tests\Feature\Api;

use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_and_mark_only_their_notifications_as_read(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $notification = MemberNotification::create([
            'user_id' => $member->id,
            'type' => 'interest_received',
            'title' => 'New interest received',
            'body' => 'A member is interested in connecting with you.',
            'action' => 'interests',
        ]);
        $otherNotification = MemberNotification::create([
            'user_id' => $other->id,
            'type' => 'message_received',
            'title' => 'New message',
            'body' => 'You received a new message.',
        ]);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonCount(1, 'data.notifications.data')
            ->assertJsonPath('data.notifications.data.0.action', 'interests');

        $this->postJson("/api/v1/notifications/{$otherNotification->id}/read")->assertNotFound();
        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id);
        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
