<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialLink;
use Illuminate\Support\Facades\Auth;

class SocialLinkController extends Controller
{
        // List all social links for the authenticated user
    public function index()
    {
        $links = Auth::user()->socialLinks()->get();
        return response()->json(['data' => $links]);
    }

    // Store a new social link
    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|max:50',
            'link' => 'required|url',
        ]);

        $link = SocialLink::create([
            'user_id' => Auth::id(),
            'platform' => $request->platform,
            'link' => $request->link,
        ]);

        return response()->json(['message' => 'Social link added successfully.', 'data' => $link], 201);
    }

    // Update existing social link
    public function update(Request $request, $id)
    {
        $link = SocialLink::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'platform' => 'sometimes|string|max:50',
            'link' => 'sometimes|url',
        ]);

        $link->update($request->only(['platform', 'link']));

        return response()->json(['message' => 'Social link updated.', 'data' => $link]);
    }

    // Delete a link
    public function destroy($id)
    {
        $link = SocialLink::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $link->delete();

        return response()->json(['message' => 'Social link deleted.']);
    }
}
