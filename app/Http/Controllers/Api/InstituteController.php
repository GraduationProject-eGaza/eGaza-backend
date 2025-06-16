<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceRequest;
use App\Models\Complaint;
use App\Models\Announcement;
use App\Models\Bill;
use App\Models\BillType;
use App\Models\ServiceType;


class InstituteController extends Controller
{

    public function updateInstituteProfile(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user || $user->type !== 'government-institute') {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'institution_name' => 'nullable|string|max:255',
                'institution_type' => 'nullable|string|max:255',
                'institution_email' => 'nullable|email|unique:users,email,' . $user->id,
                'official_phone' => 'nullable|string|max:20',
                'governorate' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'street' => 'nullable|string|max:255',
                'representative_mobile' => 'nullable|string|max:20',
                'email' => 'required|email|unique:users,email',
                'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');

                // Delete old one if exists
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('profile_pictures', $fileName, 'public');

                $validated['profile_picture'] = $filePath;
            }

            $user->fill($validated);
            $user->save();

            return response()->json([
                'message' => 'Institute profile updated successfully.',
                'user' => $user,
                'profile_picture_url' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update institute profile', 'error' => $e->getMessage()], 500);
        }
    }


    //list of employee with search
    public function listEmployees(Request $request)
    {
        $institute = Auth::user();

        if ($institute->type !== 'government-institute') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $search = $request->query('q');

        $query = User::where('institute_id', $institute->id)
            ->where('type', 'government-employee');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->select('id', 'full_name', 'username', 'email', 'profile_picture')
            ->orderBy('full_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'username' => $employee->username,
                    'email' => $employee->email,
                    'avatar' => $employee->profile_picture ? asset('storage/' . $employee->profile_picture) : null,
                ];
            });

        return response()->json(['data' => $employees]);
    }


    //dashboard statistics
    public function overview()
    {
        $institute = Auth::user();

        if ($institute->type !== 'government-institute') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $instituteId = $institute->id;

        // Count employees
        $employeeCount = User::where('institute_id', $instituteId)
            ->where('type', 'government-employee')
            ->count();

        // Count followers
        $followersCount = $institute->followers()->count();

        // Service Requests
        $totalServiceRequests = ServiceRequest::where('institute_id', $instituteId)->count();
        $serviceStatusCounts = ServiceRequest::where('institute_id', $instituteId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        // Complaints
        $totalComplaints = Complaint::where('institute_id', $instituteId)->count();
        $complaintStatusCounts = Complaint::where('institute_id', $instituteId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        // Announcements
        $totalAnnouncements = Announcement::where('institute_id', $instituteId)->count();
        $announcementStatusCounts = Announcement::where('institute_id', $instituteId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        // ✅ New: Count service types created by institute
        $serviceTypeCount = ServiceType::where('institute_id', $instituteId)->count();

        // ✅ New: Count bill types created by institute
        $billTypeCount = BillType::where('institute_id', $instituteId)->count();

        // ✅ New: Count assigned bills with status breakdown
        $assignedBillsQuery = Bill::whereHas('billType', function ($q) use ($instituteId) {
            $q->where('institute_id', $instituteId);
        });

        $billStatusCounts = $assignedBillsQuery
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json(
            [
                'employees' => $employeeCount,
                'followers' => $followersCount,

                'service_requests' => [
                    'total' => $totalServiceRequests,
                    'completed' => $serviceStatusCounts['completed'] ?? 0,
                    'pending' => $serviceStatusCounts['pending'] ?? 0,
                    'rejected' => $serviceStatusCounts['rejected'] ?? 0,
                ],

                'complaints' => [
                    'total' => $totalComplaints,
                    'resolved' => $complaintStatusCounts['resolved'] ?? 0,
                    'pending' => $complaintStatusCounts['pending'] ?? 0,
                ],

                'announcements' => [
                    'total' => $totalAnnouncements,
                    'posted' => $announcementStatusCounts['posted'] ?? 0,
                    'pending' => $announcementStatusCounts['pending'] ?? 0,
                    'rejected' => $announcementStatusCounts['rejected'] ?? 0,
                ],
                'service_types_count' => $serviceTypeCount,
                'bill_types_count' => $billTypeCount,
                'assigned_bills' => [
                    'total' => ($billStatusCounts['paid'] ?? 0) + ($billStatusCounts['unpaid'] ?? 0),
                    'paid' => $billStatusCounts['paid'] ?? 0,
                    'unpaid' => $billStatusCounts['unpaid'] ?? 0,
                ],
            ],

        );
    }
}
