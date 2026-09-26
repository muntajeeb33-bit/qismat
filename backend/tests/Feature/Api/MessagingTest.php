<?php

namespace Tests\Feature\Api;

use App\Models\Block;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private function connectedMembers(): array
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $first->profile()->create(['profile_code' => 'QSM-FIRST', 'display_name' => 'First Member']);
        $second->profile()->create(['profile_code' => 'QSM-SECOND', 'display_name' => 'Second Member']);
        Interest::create(['sender_id' => $first->id, 'receiver_id' => $second->id, 'status' => 'accepted', 'responded_at' => now()]);
        $conversation = Conversation::create(Conversation::between($first->id, $second->id));

        return [$first, $second, $conversation];
    }

    public function test_accepted_members_can_exchange_read_and_delete_messages(): void
    {
        [$first, $second, $conversation] = $this->connectedMembers();
        Sanctum::actingAs($first);

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.member.user_id', $second->id)
            ->assertJsonPath('data.0.unread_count', 0);

        $sent = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => '  Hello, thank you for connecting.  '])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Hello, thank you for connecting.')
            ->json('data');

        Sanctum::actingAs($second);
        $this->getJson('/api/v1/conversations')->assertJsonPath('data.0.unread_count', 1);
        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.data.0.mine', false);
        $this->postJson("/api/v1/conversations/{$conversation->id}/read")->assertOk();

        Sanctum::actingAs($first);
        $messages = $this->getJson("/api/v1/conversations/{$conversation->id}/messages")->assertOk();
        $this->assertNotNull($messages->json('data.data.0.read_at'));
        $this->deleteJson('/api/v1/messages/'.$sent['id'])
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('data.body', null);

        $this->assertDatabaseHas('messages', ['id' => $sent['id'], 'body' => 'Hello, thank you for connecting.']);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_non_participants_and_unmatched_members_cannot_access_a_conversation(): void
    {
        [$first, $second, $conversation] = $this->connectedMembers();
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")->assertNotFound();
        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Hello'])->assertNotFound();

        Interest::where('sender_id', $first->id)->update(['status' => 'declined']);
        Sanctum::actingAs($second);
        $this->getJson("/api/v1/conversations/{$conversation->id}/messages")->assertNotFound();
    }

    public function test_blocking_or_suspension_closes_messaging_immediately(): void
    {
        [$first, $second, $conversation] = $this->connectedMembers();
        Block::create(['blocker_id' => $first->id, 'blocked_user_id' => $second->id]);
        Sanctum::actingAs($second);
        $this->getJson('/api/v1/conversations')->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Hello'])->assertNotFound();

        Block::query()->delete();
        $second->update(['status' => 'suspended']);
        $this->getJson('/api/v1/conversations')->assertForbidden();
    }

    public function test_only_the_sender_can_delete_a_message(): void
    {
        [$first, $second, $conversation] = $this->connectedMembers();
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $first->id, 'body' => 'Hello']);
        Sanctum::actingAs($second);
        $this->deleteJson("/api/v1/messages/{$message->id}")->assertForbidden();
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }
}
