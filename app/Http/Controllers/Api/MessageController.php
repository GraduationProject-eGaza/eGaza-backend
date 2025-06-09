<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;


class MessageController extends Controller
{
    // Send a message
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        $sender = Auth::user();
        $receiver = User::find($request->receiver_id);

        // Allow citizen ↔ citizen or citizen ↔ institute only
        if (
            ($sender->type === 'citizen' && in_array($receiver->type, ['citizen', 'government-institute'])) ||
            ($sender->type === 'government-institute' && $receiver->type === 'citizen')
        ) {
            $message = Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'content' => $request->content,
            ]);
            // Send notification to receiver
            $receiver->notify(new NewMessageNotification($message));

            return response()->json(['message' => 'Message sent successfully.', 'data' => $message], 201);
        }

        return response()->json(['error' => 'Messaging not allowed between these user types.'], 403);
    }

    // Show messages between authenticated user and another user
    public function chatWith($userId)
    {
        $user = Auth::user();

        $messages = Message::where(function ($q) use ($user, $userId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($user, $userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $user->id);
        })->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $messages]);
    }

    // Inbox: messages received and sent  by the user
public function inbox()
{
    $user = Auth::user();

    $messages = Message::where(function ($query) use ($user) {
        $query->where('sender_id', $user->id)
              ->orWhere('receiver_id', $user->id);
    })
    ->with(['sender:id,full_name,profile_picture', 'receiver:id,full_name,profile_picture'])
    ->orderBy('created_at', 'desc')
    ->get();

    return response()->json(['data' => $messages]);
}

public function markMessageAsRead($id)
{
    $user = Auth::user();

    $message = Message::where('id', $id)
        ->where('receiver_id', $user->id)
        ->firstOrFail();

    if (!$message->read_at) {
        $message->read_at = now();
        $message->save();
    }

    return response()->json(['message' => 'Message marked as read']);
}
public function unreadMessageCount()
{
    $count = Message::where('receiver_id', Auth::id())
        ->whereNull('read_at')
        ->count();

    return response()->json(['unread_messages' => $count]);
}

}
