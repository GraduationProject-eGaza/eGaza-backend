<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ComplaintSubmittedNotification;

class CitizenComplaintController extends Controller
{
    public function store(Request $request) {
    $request->validate([
        'institute_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'complaint_date' => 'required|date',
    ]);

    $citizen = Auth::user();
    $institute = User::findOrFail($request->institute_id);

    if ($citizen->governorate !== $institute->governorate) {
        return response()->json(['error' => 'Institute is not in your governorate'], 403);
    }

    // Generate complaint number
    $govId = strtoupper(str_replace(' ', '-', $institute->government_id));
    $count = Complaint::where('institute_id', $institute->id)->count() + 1;
    $serial = str_pad($count, 3, '0', STR_PAD_LEFT);
    $complaintNumber = "{$govId}-C-{$serial}";

    $complaint = Complaint::create([
        'citizen_id' => $citizen->id,
        'institute_id' => $request->institute_id,
        'title' => $request->title,
        'description' => $request->description,
        'complaint_date' => $request->complaint_date,
        'complaint_number' => $complaintNumber,
    ]);
    $institute = User::find($complaint->institute_id);
$institute->notify(new ComplaintSubmittedNotification($complaint));

    return response()->json([
        'complaint_id' => $complaint->id,
        'complaint_number' => $complaint->complaint_number,
        'government_name' => $institute->institution_name,
        'title' => $complaint->title,
        'date' => $complaint->complaint_date,
        'status' => $complaint->status,
    ], 201);
}

public function index()
{
    $citizen = Auth::user();

    $complaints = Complaint::with('institute')
        ->where('citizen_id', $citizen->id)
        ->get()
        ->map(function ($complaint, $index) {
            return [
                'no' => $index + 1,
                'complaint_number' => $complaint->complaint_number,
                'government_name' => optional($complaint->institute)->institution_name,
                'title' => $complaint->title,
                'date' => $complaint->complaint_date,
                'status' => $complaint->status,
            ];
        });

    return response()->json($complaints);
}

}
