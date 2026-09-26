<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Message;
use App\Models\User;
use App\Services\ActivityTracker;
use App\Services\MemberNotifier;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $this->requireActive($request->user());
        $conversations = Conversation::query()
            ->with([
                'userOne.profile:id,user_id,profile_code,display_name',
                'userTwo.profile:id,user_id,profile_code,display_name',
                'latestMessage',
            ])
            ->where(fn ($query) => $query->where('user_one_id', $request->user()->id)->orWhere('user_two_id', $request->user()->id))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Conversation $conversation) => $this->canAccess($request->user(), $conversation))
            ->map(fn (Conversation $conversation) => $this->conversationPayload($conversation, $request->user()))
            ->values();

        return $this->success($conversations);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);
        $messages = $conversation->messages()
            ->latest('id')
            ->paginate(50)
            ->through(fn (Message $message) => $this->messagePayload($message, $request->user()));

        return $this->success($messages);
    }

    public function store(Request $request, Conversation $conversation, ActivityTracker $tracker, MemberNotifier $notifier)
    {
        $this->authorizeConversation($request->user(), $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $body = trim($data['body']);
        abort_if($body === '', 422, 'Message cannot be empty.');

        $message = $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => $body]);
        $conversation->update(['last_message_at' => $message->created_at]);
        $tracker->record($request, 'message.sent', 'conversation', $conversation->id);
        $receiverId = $conversation->user_one_id === $request->user()->id ? $conversation->user_two_id : $conversation->user_one_id;
        $notifier->send($receiverId, 'message_received', 'New message', 'You received a new message from a mutual connection.', 'messages', ['conversation_id' => $conversation->id]);

        return $this->success($this->messagePayload($message, $request->user()), 'Message sent.', 201);
    }

    public function read(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'Conversation marked as read.');
    }

    public function destroy(Request $request, Message $message, ActivityTracker $tracker)
    {
        $this->authorizeConversation($request->user(), $message->conversation);
        abort_unless($message->sender_id === $request->user()->id, 403);
        abort_if($message->deleted_at !== null, 409, 'Message is already deleted.');

        $message->update(['deleted_at' => now()]);
        $tracker->record($request, 'message.deleted', 'message', $message->id);

        return $this->success($this->messagePayload($message->fresh(), $request->user()), 'Message deleted.');
    }

    private function authorizeConversation(User $user, Conversation $conversation): void
    {
        $this->requireActive($user);
        abort_unless($conversation->includes($user) && $this->canAccess($user, $conversation), 404, 'Conversation not found.');
    }

    private function requireActive(User $user): void
    {
        abort_unless($user->status === 'active', 403, 'This account is not active.');
    }

    private function canAccess(User $user, Conversation $conversation): bool
    {
        if (! $conversation->includes($user)) {
            return false;
        }

        $otherId = $conversation->user_one_id === $user->id ? $conversation->user_two_id : $conversation->user_one_id;
        $otherIsActive = User::query()->whereKey($otherId)->where('status', 'active')->exists();
        $blocked = Block::query()->where(function ($query) use ($user, $otherId) {
            $query->where(fn ($pair) => $pair->where('blocker_id', $user->id)->where('blocked_user_id', $otherId))
                ->orWhere(fn ($pair) => $pair->where('blocker_id', $otherId)->where('blocked_user_id', $user->id));
        })->exists();
        $accepted = Interest::query()->where('status', 'accepted')->where(function ($query) use ($user, $otherId) {
            $query->where(fn ($pair) => $pair->where('sender_id', $user->id)->where('receiver_id', $otherId))
                ->orWhere(fn ($pair) => $pair->where('sender_id', $otherId)->where('receiver_id', $user->id));
        })->exists();

        return $otherIsActive && ! $blocked && $accepted;
    }

    private function conversationPayload(Conversation $conversation, User $viewer): array
    {
        $other = $conversation->user_one_id === $viewer->id ? $conversation->userTwo : $conversation->userOne;
        $latest = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'member' => [
                'user_id' => $other?->id,
                'profile_code' => $other?->profile?->profile_code,
                'display_name' => $other?->profile?->display_name ?: $other?->name,
            ],
            'last_message_at' => $conversation->last_message_at,
            'latest_message' => $latest ? $this->messagePayload($latest, $viewer) : null,
            'unread_count' => $conversation->messages()->where('sender_id', '!=', $viewer->id)->whereNull('read_at')->whereNull('deleted_at')->count(),
        ];
    }

    private function messagePayload(Message $message, User $viewer): array
    {
        return [
            'id' => $message->id,
            'mine' => $message->sender_id === $viewer->id,
            'body' => $message->deleted_at ? null : $message->body,
            'deleted' => $message->deleted_at !== null,
            'read_at' => $message->read_at,
            'created_at' => $message->created_at,
        ];
    }
}
