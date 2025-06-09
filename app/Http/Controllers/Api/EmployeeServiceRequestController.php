<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Notifications\RequestStatusUpdatedNotification;


class EmployeeServiceRequestController extends Controller
{
    public function assignedRequests(Request $request)
    {
        $user = Auth::user();

        if ($user->type !== 'government-employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $status = $request->query('status'); // optional

        $query = ServiceRequest::with(['citizen', 'serviceType'])
            ->where('assigned_to', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->get();

        $data = $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'service_number' => $request->service_number,
                'service_type' => $request->serviceType->name ?? 'N/A',
                'submitted_by' => $request->citizen->full_name,
                'submitted_at' => $request->request_date,
                'status' => $request->status,
            ];
        });

        return response()->json(['data' => $data]);
    }


    // view specifc request
    public function showAssignedRequest($id)
    {
        $user = Auth::user();

        if ($user->type !== 'government-employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request = ServiceRequest::with(['citizen', 'serviceType'])
            ->where('assigned_to', $user->id)
            ->findOrFail($id);

        return response()->json([
            'service_number' => $request->service_number,
            'service_type' => $request->serviceType->name,
            'service_description' => $request->description,
            'submitted_by' => $request->citizen->full_name,
            'submitted_at' => $request->request_date,
            'status' => $request->status,
        ]);
    }
    public function updateRequestStatus(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->type !== 'government-employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:completed,rejected',
        ]);

        $serviceRequest = ServiceRequest::where('assigned_to', $user->id)
            ->findOrFail($id);

        if ($serviceRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be updated.'], 422);
        }

        $serviceRequest->status = (string) $request->input('status');
        $serviceRequest->save();
        $citizen = $serviceRequest->citizen;
        $serviceType = ServiceType::find($serviceRequest->service_type_id); // get actual model

        $citizen->notify(new RequestStatusUpdatedNotification($serviceRequest));

        return response()->json([
            'message' => "Service request status updated to '{$request->status}'.",
            'data' => [
                'id' => $serviceRequest->id,
                'status' => $serviceRequest->status,
            ]
        ]);
    }
}
