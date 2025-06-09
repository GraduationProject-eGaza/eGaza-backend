<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bill;
use App\Models\BillType;
use App\Models\User;
use App\Notifications\BillTypeAssignedNotification;
use App\Notifications\BillPaidNotification;


class BillModule extends Controller
{
    // Institute creates a BillType
    public function createBillType(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'default_amount' => 'required|numeric|min:0',
            'assigned_employees' => 'array',
            'assigned_employees.*' => 'exists:users,id'
        ]);

        $institute = Auth::user();
        if ($institute->type !== 'government-institute') {
            return response()->json(['message' => 'Unauthorized. Only institutes can add services.'], 403);
        }

        // ✅ Get only employees that belong to this institute
        $validEmployees = User::where('institute_id', $institute->id)
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

        $billType = BillType::create([
            'institute_id' => $institute->id,
            'name' => $request->name,
            'description' => $request->description,
            'default_amount' => $request->default_amount
        ]);

        $billType->assignedEmployees()->sync($assigned);
        foreach ($billType->assignedEmployees as $emp) {
            $emp->notify(new BillTypeAssignedNotification($billType));
        }

        // ✅ Load and format assigned employees with avatar URLs
        $employees = $billType->assignedEmployees()->get()->map(function ($employee) {
            return [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'avatar_url' => $employee->profile_picture
                    ? asset('storage/' . $employee->profile_picture)
                    : null,
            ];
        });
        return response()->json([
            'message' => 'bill type created successfully.',
            'data' => [
                'id' => $billType->id,
                'name' => $billType->name,
                'description' => $billType->description,
                'assigned_employees' => $employees,
            ]
        ], 201);
    }



    // List all bills type for the logged-in institute

    public function index()
    {
        $user = Auth::user();

        if ($user->type !== 'government-institute') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $billTypes = BillType::with([
            'assignedEmployees:id,full_name',
        ])
            ->where('institute_id', $user->id)
            ->get();

        $data = $billTypes->map(function ($type, $index) {
            return [
                'no' => $index + 1, // Serial number
                'bill_type_id' => $type->id,
                'service_type' => $type->name,
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







    // Update bill type

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'default_amount' => 'required|numeric|min:0',
            'assigned_employees' => 'array',
            'assigned_employees.*' => 'exists:users,id'
        ]);

        $user = Auth::user();

        if ($user->type !== 'government-institute') {
            return response()->json(['message' => 'Unauthorized. Only institutes can update services.'], 403);
        }

        $bill = BillType::where('institute_id', $user->id)->findOrFail($id);

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

        $bill->update([
            'name' => $request->name,
           ' default_amount'=>$request->default_amount,
            'description' => $request->description,

        ]);

        $bill->assignedEmployees()->sync($assigned);
        foreach ($bill->assignedEmployees as $emp) {
            $emp->notify(new BillTypeAssignedNotification($bill));
        }

        return response()->json([
            'message' => 'bill type updated successfully.',
            'data' => $bill->load('assignedEmployees')
        ]);
    }



    // Delete bill
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->type !== 'government-institute') {
            return response()->json(['message' => 'Unauthorized. Only institutes can delete services.'], 403);
        }

        $service = BillType::where('institute_id', $user->id)->findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Service type deleted successfully.']);
    }


    //Employee assigns bill to a citizen
    public function assignBill(Request $request)
{
    $request->validate([
        'national_id' => 'required|string|exists:users,national_id',
        'bill_type_id' => 'required|exists:bill_types,id',
        'amount' => 'required|numeric|min:0',
        'due_date' => 'required|date',
    ]);

    $employee = Auth::user();
    $billType = BillType::with('institute')->findOrFail($request->bill_type_id);

    if (!$billType->assignedEmployees->contains($employee->id)) {
        return response()->json(['message' => 'Unauthorized to assign this bill type.'], 403);
    }

    // Ensure citizen exists by national ID
    $citizen = User::where('national_id', $request->national_id)
                    ->where('type', 'citizen')
                    ->first();

    if (!$citizen) {
        return response()->json(['message' => 'Citizen with this National ID not found.'], 404);
    }

    // Build custom Bill ID
    $instituteCode = strtoupper(implode('', array_map(fn($word) => $word[0], explode(' ', $billType->institute->institution_name))));
    $billTypeCode = strtoupper(implode('', array_map(fn($word) => $word[0], explode(' ', $billType->name))));
    $prefix = "{$instituteCode}-{$billTypeCode}";

    $count = Bill::whereHas('billType', function ($q) use ($billType) {
        $q->where('id', $billType->id);
    })->count() + 1;

    $billNumber = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

    $bill = Bill::create([
        'bill_type_id' => $billType->id,
        'assigned_by' => $employee->id,
        'citizen_id' => $citizen->id,
        'amount' => $request->amount,
        'due_date' => $request->due_date,
        'status' => 'unpaid',
        'bill_number' => $billNumber,
    ]);

    // Notify citizen
    $citizen->notify(new \App\Notifications\BillAssignedNotification($bill));

    return response()->json(['message' => 'Bill assigned successfully.', 'data' => $bill]);
}




 //Employee View: List of Assigned Bills
    public function employeeAssignedBills(Request $request)
{
    $employee = Auth::user();

    if ($employee->type !== 'government-employee') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $status = $request->query('status');

    $query = Bill::with(['billType', 'citizen'])
        ->where('assigned_by', $employee->id)
        ->orderBy('created_at', 'desc');

    if ($status) {
        $query->where('status', $status);
    }

    $bills = $query->get();

    $data = $bills->map(function ($bill, $index) {
        return [
            'no' => $index + 1,
            'bill_id' => $bill->bill_number,
            'bill_type' => $bill->billType->name,
            'assigned_to' => $bill->citizen->full_name,
            'amount' => $bill->amount,
            'due_date' => $bill->due_date,
            'status' => ucfirst($bill->status),
            'paid_at' => $bill->paid_at,
        ];
    });

    return response()->json(['data' => $data]);
}





// Citizen pays a bill

public function payBill($id)
{
    $citizen = Auth::user();

    $bill = Bill::with(['billType', 'assignedBy', 'billType.institute'])
                ->where('citizen_id', $citizen->id)
                ->findOrFail($id);

    if ($bill->status === 'paid') {
        return response()->json(['message' => 'Bill already paid.'], 422);
    }

    $bill->update([
        'status' => 'paid',
        'paid_at' => now()
    ]);

    // ✅ Notify the employee who assigned the bill (if exists)
    if ($bill->assignedBy) {
        $bill->assignedBy->notify(new BillPaidNotification($bill, $citizen));
    }

    // ✅ Notify the institute that owns the bill type
    if ($bill->billType && $bill->billType->institute) {
        $bill->billType->institute->notify(new BillPaidNotification($bill, $citizen));
    }

    return response()->json(['message' => 'Bill paid successfully.']);
}




    //Citizen views their bills
   public function citizenBills()
{
    $citizen = Auth::user();

    $bills = Bill::with(['billType', 'billType.institute'])
        ->where('citizen_id', $citizen->id)
        ->get();

    $data = $bills->map(function ($bill, $index) {
        return [
            'no' => $index + 1,
            'bill_id' => $bill->bill_number,
            'bill_type' => $bill->billType->name,
            'government_name' => $bill->billType->institute->institution_name,
            'amount' => $bill->amount,
            'due_date' => $bill->due_date,
            'status' => ucfirst($bill->status),
            'can_pay' => $bill->status === 'unpaid',
        ];
    });

    return response()->json(['data' => $data]);
}




    //Institute views assigned bills
    public function instituteBills(Request $request)
{
    $institute = Auth::user();
    if ($institute->type !== 'government-institute') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $status = $request->query('status');

    $query = Bill::with(['billType', 'citizen', 'assignedBy'])
        ->whereHas('billType', function ($q) use ($institute) {
            $q->where('institute_id', $institute->id);
        })
        ->orderBy('created_at', 'desc');

    if ($status) {
        $query->where('status', $status);
    }

    $bills = $query->get();

    $data = $bills->map(function ($bill, $index) {
        return [
            'no' => $index + 1,
            'bill_id' => $bill->bill_number,
            'bill_type' => $bill->billType->name,
            'assigned_by' => $bill->assignedBy->full_name,
            'assigned_to' => $bill->citizen->full_name,
            'amount' => $bill->amount,
            'due_date' => $bill->due_date,
            'status' => ucfirst($bill->status),
            'paid_at' => $bill->paid_at,
        ];
    });

    return response()->json(['data' => $data]);
}

// make sure to notify institute and employee who assign to citizen when citizen pay
// make notify to citizen when employee assign bill to it

//make test with insomina for this endpoint
}
