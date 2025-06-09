<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ComplaintAssignedNotification;


class InstituteComplaintController extends Controller
{
    public function getInstituteComplaints(Request $request)
    {
        $user = Auth::user(); // institute is authenticated user

        if ($user->type !== 'government-institute') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $status = $request->query('status'); // optional

        $query = Complaint::where('institute_id', $user->id)
            ->with(['citizen'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $complaints = $query->get();


    $data = $complaints->values()->map(function ($complaint, $index)  {
        return [
            'no' => $index + 1,
            'complaint_id' => $complaint->complaint_number,
            'title' => $complaint->title,
            'submitted_by' => $complaint->citizen->full_name ?? 'N/A',
            'submitted_at' => $complaint->complaint_date,
            'status' => ucfirst($complaint->status),
            'assigned_to' => $complaint->assignedEmployee ? [
                'name' => $complaint->assignedEmployee->full_name,
                'avatar' => $complaint->assignedEmployee->profile_picture ?? null,
            ] : null,
        ];
    });

    return response()->json(['data' => $data]);
 }
    public function assignComplaint(Request $request, $complaintId)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);

        $institute = Auth::user();

        if ($institute->type !== 'government-institute') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $complaint = Complaint::where('institute_id', $institute->id)->findOrFail($complaintId);
        $employee = User::findOrFail($request->employee_id);

        if (
        $complaint->institute_id !== $institute->id ||
        $employee->institute_id !== $institute->id ||  // Make sure it's `institute_id` not `institution_id`
        $employee->type !== 'government-employee'
    ) {
        return response()->json([
            'message' => 'Unauthorized assignment: employee or complaint does not belong to your institute.'
        ], 403);
    }
        $complaint->assigned_to = $employee->id;
        $complaint->save();
        $employee->notify(new ComplaintAssignedNotification($complaint));


        return response()->json(['message' => 'Complaint assigned successfully']);
    }
}
