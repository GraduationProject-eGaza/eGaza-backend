<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
        public function updateEmployeeProfile(Request $request)
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
}
