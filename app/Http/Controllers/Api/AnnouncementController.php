<?php
namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Notifications\AnnouncementCreated;
use App\Notifications\AnnouncementApproved;
use App\Notifications\AnnouncementRejected;
use App\Notifications\AnnouncementPublished;
use Illuminate\Support\Str;


class AnnouncementController extends Controller
{
    // ========== Employee submits announcement ==========
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->type !== 'government-employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'date' => 'required|date',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = $request->file('file')?->store('announcements', 'public');

        $announcement = Announcement::create([
            'title' => $request->title,
            'description' => $request->description,
            'announcement_date' => $request->date,
            'media_path' => $path,
            'employee_id' => $user->id,
            'institute_id' => $user->institute_id,
            'status' => 'pending',
        ]);

        // Notify the institute
        $institute = $user->institute;
        if ($institute) {
            $institute->notify(new AnnouncementCreated($announcement));
        }

        return response()->json(['message' => 'Announcement submitted for approval.']);
    }

    // ========== Institute approves ==========
    public function approve($id)
    {
        $user = Auth::user();

        if ($user->type !== 'government-institute') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $announcement = Announcement::where('id', $id)
            ->where('institute_id', $user->id)
            ->firstOrFail();

        $announcement->status = 'approved';
        $announcement->save();

        // Notify employee
        $announcement->employee->notify(new AnnouncementApproved($announcement));

        // Notify followers (citizens)
        $followers = $user->followers ?? [];
        foreach ($followers as $citizen) {
            $citizen->notify(new AnnouncementPublished($announcement));
        }

        return response()->json(['message' => 'Announcement approved and published.']);
    }

    // ========== Institute rejects ==========
    public function reject($id)
    {
        $user = Auth::user();

        if ($user->type !== 'government-institute') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $announcement = Announcement::where('id', $id)
            ->where('institute_id', $user->id)
            ->firstOrFail();

        $announcement->status = 'rejected';
        $announcement->save();

        $announcement->employee->notify(new AnnouncementRejected($announcement));

        return response()->json(['message' => 'Announcement rejected.']);
    }

    // ========== Citizen sees followed announcements ==========
    public function citizenFeed()
    {
        $user = Auth::user();
        if ($user->type !== 'citizen') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $announcements = Announcement::withCount(['views', 'likes', 'comments'])
            ->where('status', 'approved')
            ->whereIn('institute_id', $user->followingInstitutes->pluck('id'))
            ->latest()
            ->get();

        return response()->json([
            'announcements' => $announcements->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'description' => $announcement->description,
                    'announcement_date' => $announcement->announcement_date,
                    'image_url' => $announcement->file_url, // Accessor in model
                    'views_count' => $announcement->views_count,
                    'likes_count' => $announcement->likes_count,
                    'comments_count' => $announcement->comments_count,
                ];
            })
        ]);
    }



    public function showAnnouncementForCitizen($id)
    {
        $user = Auth::user();

        if ($user->type !== 'citizen') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $announcement = Announcement::with(['employee', 'institute', 'comments'])
            ->withCount(['likes', 'comments', 'views'])
            ->where('status', 'approved') // only approved announcements
            ->whereHas('institute', function ($query) use ($user) {
                $query->whereHas('followers', function ($q) use ($user) {
                    $q->where('citizen_id', $user->id);
                });
            })
            ->findOrFail($id);

        // Record that this citizen has seen the announcement
        $announcement->views()->firstOrCreate([
            'citizen_id' => $user->id,
        ]);

        return response()->json([
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'description' => $announcement->description,
                'date' => $announcement->announcement_date,
                'media_path' => $announcement->media_path, // you must set this in model accessor
                'image_url' => $announcement->media_path ? asset('storage/' . $announcement->media_path) : null,

                'likes_count' => $announcement->likes()->count(),
                'comments_count' => $announcement->comments()->count(),
                'views_count' => $announcement->views()->count(),
                'comments' => $announcement->comments,
            ]
        ]);
    }


    // ========== Citizen likes ==========
    public function like($id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        if (!$announcement->likes()->where('citizen_id', $user->id)->exists()) {
            $announcement->likes()->create(['citizen_id' => $user->id]);
        }

        return response()->json(['message' => 'Liked']);
    }

    // ========== Citizen comments ==========
    public function comment(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        $request->validate([
            'comment' => 'required|string',
        ]);

        $announcement->comments()->create([
            'citizen_id' => $user->id,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Comment added']);
    }

    // ========== Citizen marks as seen ==========
    public function markAsSeen($id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = Auth::user();

        if (!$announcement->views()->where('citizen_id', $user->id)->exists()) {
            $announcement->views()->create(['citizen_id' => $user->id]);
        }

        return response()->json(['message' => 'Marked as seen']);
    }


    //===============posted announcments list for insititute  ==============


public function instituteAnnouncements()
{
    $user = Auth::user();

    if ($user->type !== 'government-institute') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $announcements = Announcement::withCount(['likes', 'comments', 'views'])
        ->where('institute_id', $user->id)
        ->where('status', 'approved')
        ->latest()
        ->get();

    return response()->json(['announcements' => $announcements]);
}



//==============Can update or delete an announcement only if it's approved (i.e., posted to citizens).



public function destroyByInstitute($id)
{
    $user = Auth::user();

    $announcement = Announcement::where('id', $id)
        ->where('institute_id', $user->id)
        ->where('status', 'approved')
        ->firstOrFail();

    // Delete media
    if ($announcement->media_path && Storage::disk('public')->exists($announcement->media_path)) {
        Storage::disk('public')->delete($announcement->media_path);
    }

    $announcement->delete();

    return response()->json(['message' => 'Announcement deleted by institute']);
}


public function updateByInstitute(Request $request, $id)
{
    $user = Auth::user();

    $announcement = Announcement::where('id', $id)
        ->where('institute_id', $user->id)
        ->where('status', 'approved')
        ->firstOrFail();

    $request->validate([
        'title' => 'required|string',
        'description' => 'required|string',
        'date' => 'required|date',
        'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $announcement->title = $request->title;
    $announcement->description = $request->description;
    $announcement->announcement_date = $request->date;

    if ($request->hasFile('file')) {
        // Delete old file
        if ($announcement->media_path && Storage::disk('public')->exists($announcement->media_path)) {
            Storage::disk('public')->delete($announcement->media_path);
        }

        // Upload new file
        $file = $request->file('file');
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('announcements', $fileName, 'public');
        $announcement->media_path = $filePath;
    }

    $announcement->save();

    return response()->json(['message' => 'Announcement updated by institute']);
}


//==============Can update or delete an announcement only if it's still pending or rejected.

public function destroyByEmployee($id)
{
    $user = Auth::user();

    $announcement = Announcement::where('id', $id)
        ->where('employee_id', $user->id)
        ->whereIn('status', ['pending', 'rejected'])
        ->firstOrFail();

    // Delete media file
    if ($announcement->media_path && Storage::disk('public')->exists($announcement->media_path)) {
        Storage::disk('public')->delete($announcement->media_path);
    }

    $announcement->delete();

    return response()->json(['message' => 'Announcement deleted successfully']);
}





public function updateByEmployee(Request $request, $id)
{
    $user = Auth::user();

    $announcement = Announcement::where('id', $id)
        ->where('employee_id', $user->id)
        ->whereIn('status', ['pending', 'rejected'])
        ->firstOrFail();

   $request->validate([
        'title' => 'required|string',
        'description' => 'required|string',
        'date' => 'required|date',
        'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $announcement->title = $request->title;
    $announcement->description = $request->description;
    $announcement->announcement_date = $request->date;

    if ($request->hasFile('file')) {
        // Delete old file
        if ($announcement->media_path && Storage::disk('public')->exists($announcement->media_path)) {
            Storage::disk('public')->delete($announcement->media_path);
        }

        // Upload new file
        $file = $request->file('file');
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('announcements', $fileName, 'public');
        $announcement->media_path = $filePath;

    }

    $announcement->save();

    return response()->json(['message' => 'Announcement updated successfully']);
}



public function getAllByEmployee()
{
    $user = Auth::user();

    $announcements = Announcement::where('employee_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => true,
        'data' => $announcements,
    ]);
}

public function showForInstitute($id)
{
    $user = Auth::user(); // institute
    $announcement = Announcement::where('id', $id)
        ->where('institute_id', $user->id)
        ->firstOrFail();

    return response()->json([
        'id' => $announcement->id,
        'title' => $announcement->title,
        'description' => $announcement->description,
        'announcement_date' => $announcement->announcement_date,
        'status' => $announcement->status,
        'media_path' => $announcement->media_path ? asset('storage/' . $announcement->media_path) : null,
        'submitted_by' => $announcement->employee->full_name ?? 'Unknown',
    ]);
}

}
