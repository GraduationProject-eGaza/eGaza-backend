<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function search(Request $request)
{
    $user = Auth::user();

    // Only allow citizens to use search
    if (!in_array($user->type, ['citizen', 'government-institute'])) {
        return response()->json(['error' => 'Only citizens or government institutes can use search.'], 403);
    }

    $keyword = $request->input('q');

    if (!$keyword) {
        return response()->json(['error' => 'Search keyword is required.'], 422);
    }

    // Get all matching citizens excluding self
    $citizens = User::where('type', 'citizen')
        ->where('id', '!=', $user->id)
        ->where('full_name', 'LIKE', "%{$keyword}%")
        ->select('id', 'full_name', 'profile_picture')
        ->get(); // <-- don't limit here

    // Get all matching institutes
    $institutes = User::where('type', 'government-institute')
        ->where('institution_name', 'LIKE', "%{$keyword}%")
        ->select('id', 'institution_name as full_name', 'profile_picture')
        ->get();

    return response()->json([
        'citizens' => $citizens,
        'institutes' => $institutes
    ]);
}



public function show($id)
{
    $authUser = auth()->user();
    $user = User::findOrFail($id);

    // Optional privacy rule: don't allow viewing inactive or non-public profiles
    if ($user->status !== 'active') {
        return response()->json(['message' => 'This user is not available.'], 403);
    }

    // Build response
    return response()->json([
        'id' => $user->id,
        'full_name' => $user->full_name,
        'username' => $user->username,
        'type' => $user->type,
        'email' => $user->email,
        'phone' => $user->phone,
        'governorate' => $user->governorate,
        'city' => $user->city,
        'bio' => $user->bio,
        'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,

        // Connected Profiles
        'social_links' => $user->socialLinks->map(function ($link) {
            return [
                'platform' => $link->platform,
                'link' => $link->link,
            ];
        }),

        // Friendship or follow insights (optional)
        'friend_count' => $user->friends()->count(),
        'follwers_count' => $user->followers()->count(),
        'is_friend' => $authUser->friends->contains($user->id),
        'is_following' => $authUser->followingInstitutes->contains($user->id),
    ]);
}
}
