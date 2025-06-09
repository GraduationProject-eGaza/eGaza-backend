<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewServiceRequestNotification;


class CitizenServiceRequestController extends Controller
{

    public function index()
    {
        $citizen = Auth::user();

        $requests = ServiceRequest::with(['serviceType', 'institute'])
            ->where('citizen_id', $citizen->id)
            ->get()
            ->map(function ($request, $index) {
                return [
                    'no' => $index + 1,
                    'service_number'=>$request->service_number,
                    'government_name' => $request->institute->institution_name,
                    'service_type' => $request->serviceType->name,
                    'date' => $request->request_date,
                    'status' => $request->status,
                ];
            });

        return response()->json($requests);
    }

    public function store(Request $request)
{
    $request->validate([
        'institute_id' => 'required|exists:users,id',
        'service_type_id' => 'required|exists:service_types,id',
        'description' => 'required|string',
        'request_date' => 'required|date',
    ]);

    $citizen = Auth::user();

    $serviceType = ServiceType::findOrFail($request->service_type_id);

    if ($serviceType->institute_id != $request->institute_id) {
        return response()->json(['error' => 'Selected service type does not belong to the chosen institute'], 422);
    }

    $institute = User::findOrFail($request->institute_id);

    if ($citizen->governorate !== $institute->governorate) {
        return response()->json(['error' => 'Institute is not in your governorate'], 403);
    }

    // Get the next serial number for this government
    $govId = strtoupper(str_replace(' ', '-', $institute->government_id));

    $count = ServiceRequest::whereHas('institute', function ($query) use ($govId) {
        $query->where('government_id', $govId);
    })->count() + 1;

    $serial = str_pad($count, 3, '0', STR_PAD_LEFT);
    $serviceNumber = "{$govId}-{$serial}";

    // Store the request
    $serviceRequest = ServiceRequest::create([
        'citizen_id' => $citizen->id,
        'institute_id' => $request->institute_id,
        'service_type_id' => $request->service_type_id,
        'description' => $request->description,
        'request_date' => $request->request_date,
        'status' => 'pending',
        'service_number' => $serviceNumber,
    ]);
$institute->notify(new NewServiceRequestNotification($serviceRequest ,$citizen));

    return response()->json([
        'service_num' => $serviceRequest->id,
        'service_number' => $serviceRequest->service_number,
        'government_name' => $institute->institution_name,
        'service_type' => $serviceRequest->serviceType->name,
        'date' => $serviceRequest->request_date,
        'status' => $serviceRequest->status,
    ], 201);

}
    // for dropdown government name when make requst
    public function getInstitutesByGovernorate()
    {
        $citizen = Auth::user();

        $institutes = User::where('type', 'government-institute')
            ->where('governorate', $citizen->governorate)
            ->select('id', 'institution_name')
            ->get();

        return response()->json($institutes);
    }

    public function getServiceTypesByInstitute($id)
{
    // Check the institute exists
    $institute = User::where('type', 'government-institute')->findOrFail($id);

    // Get service types that belong to this institute
    $serviceTypes = ServiceType::where('institute_id', $id)
        ->select('id', 'name')
        ->get();

    return response()->json($serviceTypes);
}

}
