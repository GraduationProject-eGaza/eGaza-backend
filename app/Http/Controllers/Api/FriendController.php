<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Friend;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\FriendRequestAccepted;
use App\Notifications\FriendRequestSent;




class FriendController extends Controller
{
    public function sendFriendRequest(Request $request)
    {
        $request->validate(['receiver_id' => 'required|exists:users,id']);
        $receiver = User::findOrFail($request->receiver_id);
        if ($request->receiver_id == auth()->id()) {
            return response()->json(['message' => 'You cannot send request to yourself'], 400);
        }

        // Check for any existing request in both directions
        $existingRequest = FriendRequest::where(function ($query) use ($request) {
            $query->where('sender_id', auth()->id())
                ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($query) use ($request) {
            $query->where('sender_id', $request->receiver_id)
                ->where('receiver_id', auth()->id());
        })->first();

        // Block if pending or accepted
        if ($existingRequest && in_array($existingRequest->status, ['pending', 'accepted'])) {
            return response()->json(['message' => 'Friend request already exists or you are already friends'], 409);
        }

        // Delete cancelled request if exists
        if ($existingRequest && $existingRequest->status === 'cancelled') {
            $existingRequest->delete();
        }

        // Create new friend request
        FriendRequest::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'status' => 'pending'
        ]);
        // Create notification for receiver
        $receiver->notify(new FriendRequestSent(Auth::user()));


        return response()->json(['message' => 'Friend request sent']);
    }

    public function respondToFriendRequest(Request $request, $request_id)
    {
        $request->validate([
            'status' => 'required|in:accepted,cancelled'
        ]);
        // Get the friend request
        $friendRequest = FriendRequest::where('id', $request_id)
            ->where('receiver_id', auth()->id())
            ->firstOrFail();

        // Get sender user
        $sender = User::findOrFail($friendRequest->sender_id);
        $friendRequest = FriendRequest::where('id', $request_id)
            ->where('receiver_id', auth()->id())
            ->firstOrFail();

        // Update request status
        $friendRequest->update(['status' => $request->status]);

        // If accepted, create a two-way friendship
        if ($request->status === 'accepted') {
            Friend::create([
                'user_id' => $friendRequest->sender_id,
                'friend_id' => $friendRequest->receiver_id,
            ]);

            Friend::create([
                'user_id' => $friendRequest->receiver_id,
                'friend_id' => $friendRequest->sender_id,
            ]);
            // Create notification for sender
            $sender->notify(new FriendRequestAccepted(Auth::user()));
        }

        return response()->json(['message' => 'Friend request ' . $request->status]);
    }


    public function unfriend($friend_id)
    {
        $user_id = auth()->id();

        // Delete both directions of the friendship
        $deleted = Friend::where(function ($query) use ($user_id, $friend_id) {
            $query->where('user_id', $user_id)->where('friend_id', $friend_id);
        })->orWhere(function ($query) use ($user_id, $friend_id) {
            $query->where('user_id', $friend_id)->where('friend_id', $user_id);
        })->delete();

        if ($deleted) {
            // Also mark the old friend request as cancelled
            FriendRequest::where(function ($query) use ($user_id, $friend_id) {
                $query->where('sender_id', $user_id)->where('receiver_id', $friend_id);
            })->orWhere(function ($query) use ($user_id, $friend_id) {
                $query->where('sender_id', $friend_id)->where('receiver_id', $user_id);
            })->where('status', 'accepted')
                ->update(['status' => 'cancelled']);

            return response()->json(['message' => 'Unfriended successfully']);
        } else {
            return response()->json(['message' => 'Friendship not found'], 404);
        }
    }


    public function listFriends()
    {
        return response()->json(auth()->user()->friends);
    }

    public function countFriends()
    {
        $user = Auth::user();

        // Count entries where the citizen is either user_id or friend_id
        $count = DB::table('friends')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('friend_id', $user->id);
            })
            ->count();

        return response()->json(['friends_count' => $count / 2]);
    }



    //search from list
    public function searchFriends(Request $request)
    {
        $user = Auth::user();

        if ($user->type !== 'citizen') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $search = $request->query('search');

        $friends = $user->friends()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%$search%")
                        ->orWhere('username', 'like', "%$search%");
                });
            })
            ->get()
            ->map(function ($friend) {
                return [
                    'id' => $friend->id,
                    'full_name' => $friend->full_name,
                    'username' => $friend->username,
                    'avatar' => $friend->profile_picture ? asset('storage/' . $friend->profile_picture) : null,
                ];
            });

        return response()->json(['data' => $friends]);
    }
}
