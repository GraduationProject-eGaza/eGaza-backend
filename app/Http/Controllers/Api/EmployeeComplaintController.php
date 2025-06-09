<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ComplaintStatusChangedNotification;

class EmployeeComplaintController extends Controller
{
    public function getAssignedComplaints(Request $request)
    {
        $user = Auth::user();

        if ($user->type !== 'government-employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $status = $request->query('status'); // optional
        $query = Complaint::where('assigned_to', $user->id)
            ->with(['citizen', 'institute'])
            ->latest();


        if ($status) {
            $query->where('status', $status);
        }

        $complaints = $query->get();


        $data = $complaints->values()->map(function ($complaint, $index) {
            return [
                'no' => $index + 1,
                'complaint_id' => $complaint->complaint_number,
                'title' => $complaint->title,
                'submitted_by' => $complaint->citizen->full_name ?? 'N/A',
                'submitted_at' => $complaint->complaint_date,
                'status' => ucfirst($complaint->status),

            ];
        });

        return response()->json(['data' => $data]);
    }

    public function showComplaint($id)
    {
        $user = Auth::user();

        if ($user->type !== 'government-employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $complaint = Complaint::with(['citizen', 'institute'])
            ->where('assigned_to', $user->id)
            ->findOrFail($id);
        return response()->json([
            'complaint_number' => $complaint->complaint_number,
            'title' => $complaint->title,
            'complaint_description' => $complaint->description,
            'submitted_by' => $complaint->citizen->full_name,
            'submitted_at' => $complaint->complaint_date,
            'status' => $complaint->status,
        ]);
    }
    public function updateComplaintStatus(Request $request, $complaintId)
    {
        $request->validate([
            'status' => 'required|in:resolved,rejected',
        ]);

        $employee = Auth::user();

        if ($employee->type !== 'government-employee') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $complaint = Complaint::where('assigned_to', $employee->id)->findOrFail($complaintId);

        if ($complaint->status !== 'pending') {
            return response()->json(['error' => 'Complaint already handled'], 422);
        }

        $complaint->status = $request->status;
        $complaint->save();
        $citizen = $complaint->citizen;
        $citizen->notify(new ComplaintStatusChangedNotification($complaint));


        return response()->json(['message' => "Complaint marked as {$request->status}"]);
    }
}
