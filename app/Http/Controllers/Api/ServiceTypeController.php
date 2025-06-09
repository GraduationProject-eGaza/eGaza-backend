<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceType;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\RequestAssignedNotification;
use App\Notifications\ServiceTypeAssignedNotification;

class ServiceTypeController extends Controller
{
    // List all services for the logged-in institute

public function index()
{
    $user = Auth::user();

    if ($user->type !== 'government-institute') {
        return response()->json(['error' => 'Unauthorized.'], 403);
    }

    $serviceTypes = ServiceType::with([
        'assignedEmployees:id,full_name',
    ])
    ->withCount('serviceRequests')
    ->where('institute_id', $user->id)
    ->get();

    $data = $serviceTypes->map(function ($type, $index) {
        return [
            'no' => $index + 1, // Serial number
            'service_type_id' => $type->id,
            'service_type' => $type->name,
            'request_count' => $type->service_requests_count,
            'assigned_employees' => $type->assignedEmployees->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'full_name' => $emp->full_name,
                ];
            }),
        ];
    });

    return response()->json(['data' => $data]);
}
    // Store new service
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'assigned_employees' => 'array',
            'assigned_employees.*' => 'integer|exists:users,id',
        ]);

        $user = Auth::user();

        if ($user->type !== 'government-institute') {
            return response()->json(['message' => 'Unauthorized. Only institutes can add services.'], 403);
        }

        // ✅ Get only employees that belong to this institute
        $validEmployees = User::where('institute_id', $user->id)
            ->where('type', 'government-employee')
            ->pluck('id')
            ->toArray();

        $assigned = $request->input('assigned_employees', []);

        // ❌ Filter out invalid employee IDs
        $invalid = array_diff($assigned, $validEmployees);

        if (count($invalid)) {
            return response()->json([
                'message' => 'One or more employees do not belong to your institute.',
                'invalid_employee_ids' => $invalid
            ], 422);
        }

        // ✅ Create the service type
        $service = ServiceType::create([
            'institute_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $service->assignedEmployees()->sync($assigned);
        foreach ($service->assignedEmployees as $emp) {
    $emp->notify(new ServiceTypeAssignedNotification($service));
}

  // ✅ Load and format assigned employees with avatar URLs
    $employees = $service->assignedEmployees()->get()->map(function ($employee) {
        return [
            'id' => $employee->id,
            'full_name' => $employee->full_name,
            'avatar_url' => $employee->profile_picture
                ? asset('storage/' . $employee->profile_picture)
                : null,
        ];
    });

    return response()->json([
        'message' => 'Service type created successfully.',
        'data' => [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'assigned_employees' => $employees,
        ]
    ], 201);
    }

    // Update service

    public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string',
        'description' => 'nullable|string',
        'assigned_employees' => 'array',
        'assigned_employees.*' => 'integer|exists:users,id',
    ]);

    $user = Auth::user();

    if ($user->type !== 'government-institute') {
        return response()->json(['message' => 'Unauthorized. Only institutes can update services.'], 403);
    }

    $service = ServiceType::where('institute_id', $user->id)->findOrFail($id);

    $validEmployees = User::where('institute_id', $user->id)
        ->where('type', 'government-employee')
        ->pluck('id')
        ->toArray();

    $assigned = $request->input('assigned_employees', []);
    $invalid = array_diff($assigned, $validEmployees);

    if (count($invalid)) {
        return response()->json([
            'message' => 'One or more employees do not belong to your institute.',
            'invalid_employee_ids' => $invalid
        ], 422);
    }

    $service->update([
        'name' => $request->name,
        'description' => $request->description,
    ]);

    $service->assignedEmployees()->sync($assigned);
    foreach ($service->assignedEmployees as $emp) {
    $emp->notify(new ServiceTypeAssignedNotification($service));
}

    return response()->json([
        'message' => 'Service type updated successfully.',
        'data' => $service->load('assignedEmployees')
    ]);
}


    // Delete service
public function destroy($id)
{
    $user = Auth::user();

    if ($user->type !== 'government-institute') {
        return response()->json(['message' => 'Unauthorized. Only institutes can delete services.'], 403);
    }

    $service = ServiceType::where('institute_id', $user->id)->findOrFail($id);
    $service->delete();

    return response()->json(['message' => 'Service type deleted successfully.']);
}




// List incoming service requests for the logged-in institute

public function incomingRequests(Request $request)
{
    $user = Auth::user();

    if ($user->type !== 'government-institute') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $statusFilter = $request->query('status');

    // Build base query
    $query = ServiceRequest::with(['citizen', 'serviceType', 'assignedEmployee'])
        ->where('institute_id', $user->id)
        ->orderBy('created_at', 'desc');

    if ($statusFilter) {
        $query->where('status', $statusFilter);
    }

    $requests = $query->get();

    // Generate government ID prefix for fallback service number
    $govId = strtoupper(str_replace(' ', '-', $user->government_id));

    $data = $requests->values()->map(function ($request, $index) use ($govId) {
        return [
            'no' => $index + 1,
            'service_id' => $request->service_number ?? "{$govId}-" . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
            'service_type' => $request->serviceType->name ?? 'N/A',
            'submitted_by' => $request->citizen->full_name
                ?? ($request->citizen->first_name ?? '') . ' ' . ($request->citizen->last_name ?? ''),
            'submitted_at' => $request->request_date,
            'status' => ucfirst($request->status),
            'assigned_to' => $request->assignedEmployee ? [
                'name' => $request->assignedEmployee->full_name,
                'avatar' => $request->assignedEmployee->profile_picture ?? null,
            ] : null,
        ];
    });

    return response()->json(['data' => $data]);
}



// to assign button at the incoming list
public function assignEmployeeToRequest(Request $request)
{
    $request->validate([
        'service_request_id' => 'required|exists:service_requests,id',
        'employee_id' => 'required|exists:users,id',
    ]);

    $institute = Auth::user();

    $serviceRequest = ServiceRequest::findOrFail($request->service_request_id);
    $employee = User::findOrFail($request->employee_id);

    // Check 1: Both the request and employee must belong to the same institute
    if (
        $serviceRequest->institute_id !== $institute->id ||
        $employee->institute_id !== $institute->id ||
        $employee->type !== 'government-employee'
    ) {
        return response()->json(['message' => 'Unauthorized assignment: employee or request does not belong to your institute.'], 403);
    }

    // Check 2: Ensure the employee is assigned to this service type
    $serviceType = $serviceRequest->serviceType;

    $isAssigned = $serviceType->assignedEmployees()->where('users.id', $employee->id)->exists();

    if (!$isAssigned) {
        return response()->json([
            'message' => 'This employee is not assigned to the requested service type.'
        ], 422);
    }

    // Assign employee to the request
    $serviceRequest->assigned_to = $employee->id;
    $serviceRequest->save();
    $employee->notify(new RequestAssignedNotification($serviceRequest));

    return response()->json([
        'message' => 'Employee assigned successfully.',
        'request_id' => $serviceRequest->id,
        'assigned_employee' => $employee->full_name,
        'service_type' => $serviceType->name,
    ]);
}



}
