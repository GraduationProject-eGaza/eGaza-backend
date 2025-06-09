<?php

namespace App\Http\Controllers\Api;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Notifications\CitizenFollowedInstitute;
use App\Models\Notification;


class FollowController extends Controller
{
    public function follow(Request $request)
    {
        $request->validate([
            'government_institute_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->government_institute_id);
        $institute = User::findOrFail($request->government_institute_id);

        if ($user->type !== 'government-institute') {
            return response()->json(['message' => 'You can only follow government institutes'], 400);
        }

        $exists = Follow::where('citizen_id', Auth::id())
            ->where('government_institute_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already followed'], 400);
        }

        Follow::create([
            'citizen_id' => Auth::id(),
            'government_institute_id' => $user->id,
        ]);
        // Send Notification
        $institute->notify(new CitizenFollowedInstitute(Auth::user()));

        return response()->json(['message' => 'Followed successfully']);
    }

    public function unfollow(Request $request)
    {
        $request->validate([
            'government_institute_id' => 'required|exists:users,id',
        ]);

        $deleted = Follow::where('citizen_id', Auth::id())
            ->where('government_institute_id', $request->government_institute_id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Unfollowed successfully']);
        }

        return response()->json(['message' => 'You are not following this institute'], 400);
    }

    public function listFollows()
    {
        $institutes = Follow::where('citizen_id', Auth::id())
            ->with('institute')
            ->get()
            ->map(function ($follow) {
                return $follow->government_institute_id;
            });

        return response()->json(['followed_institutes' => $institutes]);
    }

    public function searchFollowedInstitutes(Request $request)
    {
        $citizen = Auth::user();

        if ($citizen->type !== 'citizen') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $search = $request->query('q');

        $query = $citizen->followingInstitutes()
            ->select('users.id', 'users.institution_name', 'users.username', 'users.profile_picture');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('institution_name', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%");
            });
        }

        $institutes = $query->get()->map(function ($institute) {
            return [
                'id' => $institute->id,
                'name' => $institute->institution_name,
                'username' => $institute->username,
                'avatar' => $institute->profile_picture ? asset('storage/' . $institute->profile_picture) : null,
            ];
        });

        return response()->json(['data' => $institutes]);
    }
}
