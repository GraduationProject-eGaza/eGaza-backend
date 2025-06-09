<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CitizenController extends Controller
{
    public function updateCitizenProfile(Request $request)
{
       try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            $validated = $request->validate([
                'full_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'gender' => 'nullable|in:male,female,other',
                'date_of_birth' => 'nullable|date',
                'governorate' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'street' => 'nullable|string|max:255',
                'nearest_landmark' => 'nullable|string|max:255',
                'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');

                // Delete old profile picture if it exists
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                // Store new file in storage/app/public/profile_pictures
                $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('profile_pictures', $fileName, 'public');

                $validated['profile_picture'] = $filePath;
            }

            $user->fill($validated);
            $user->save();

            return response()->json(['massege'=>'Profile updated successfully.',
                'user' => $user,
                'profile_picture_url' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null
            ], 200);
        } catch (\Exception $e) {
            return response()->json('Failed to update profile: ' . $e->getMessage(), 500);
        }
}

// to share button
public function show($username)
{
    $user = User::where('username', $username)->firstOrFail();

    $base = [
        'username' => $user->username,
        'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
        'type' => $user->type,
        'share_url' => url("/public-profile/{$user->username}"),

    ];

    if ($user->type === 'citizen') {
        return response()->json(array_merge($base, [
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'region' => $user->governorate . ', ' . $user->city,
            'connected_profiles' => $user->socialLinks->map(fn($link) => [
                'platform' => $link->platform,
                'link' => $link->link
            ]),
            'friends_count' => $user->friends()->count(),
        ]));
    }

    if ($user->type === 'government-institute') {
        return response()->json(array_merge($base, [
            'institution_name' => $user->institution_name,
            'institution_type' => $user->institution_type,
            'email' => $user->institution_email,
            'phone' => $user->official_phone,
            'region' => $user->governorate . ', ' . $user->city,
            'followers_count' => $user->followers()->count(),
        ]));
    }

    if ($user->type === 'government-employee') {
        return response()->json(array_merge($base, [
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'institute' => $user->institute?->institution_name ?? null,
            'region' => $user->governorate . ', ' . $user->city,
        ]));
    }

    return response()->json(['error' => 'Invalid user type.'], 422);
}



}
