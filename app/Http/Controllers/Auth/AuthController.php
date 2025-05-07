<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\SendVerificationCode;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\PasswordResetToken;

class AuthController extends Controller
{
    public function __construct(private SendVerificationCode $sendVerificationCode) {}

public function register(Request $request)
{


    $validated=   $request->validate([
'type' => 'required|in:citizen,government-institute,government-employee',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:6',

        // Citizen fields
        'full_name' => 'required_if:type,citizen,government-employee',
        'national_id' => 'required_if:type,citizen,government-employee',
        'username' => 'required_if:type,citizen,government-employee',
        'phone' => 'required_if:type,citizen,government-employee',
        'address' => 'required_if:type,citizen,government-employee',

        // Government Institute fields
        'institution_name' => 'required_if:type,government-institute',
        'institution_type' => 'required_if:type,government-institute',
        'institution_email' => 'required_if:type,government-institute',
        'official_phone' => 'required_if:type,government-institute',
        'government_id' => 'required_if:type,government-institute,government-employee',
        'institution_address' => 'required_if:type,government-institute',
        'representative_national_id' => 'required_if:type,government-institute',
        'representative_mobile' => 'required_if:type,government-institute',

        // Government Employee
        'employee_id' => 'required_if:type,government-employee',
    ]
    );
    // Prepare user data
    $userData = [
        'type' => $validated['type'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'status' => 'inactive',
        'email_verified_code' => rand(100000, 999999),
        'email_verified_at' => null,
    ];

    // Merge extra fields by type
    switch ($validated['type']) {
        case 'citizen':
            $userData += [
                'full_name' => $validated['full_name'],
                'national_id' => $validated['national_id'],
                'username' => $validated['username'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ];
            break;

        case 'government-institute':
            $userData += [
                'institution_name' => $validated['institution_name'],
                'institution_type' => $validated['institution_type'],
                'institution_email' => $validated['institution_email'],
                'official_phone' => $validated['official_phone'],
                'government_id' => $validated['government_id'],
                'institution_address' => $validated['institution_address'],
                'representative_national_id' => $validated['representative_national_id'],
                'representative_mobile' => $validated['representative_mobile'],
            ];
            break;

        case 'government-employee':
            $userData += [
                'full_name' => $validated['full_name'],
                'national_id' => $validated['national_id'],
                'username' => $validated['username'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'employee_id' => $validated['employee_id'],
                'government_id' => $validated['government_id'],
            ];
            break;
    }

    $user = User::create($userData);

    try {
        // Send verification code
       $this->sendVerificationCode->sendEmail($user);

  return response()->json([ 'message' => 'Registered successfully. Please check your email to verify your account.',
        'user' => $user,
    ],201);
}catch (Exception $e) {
        // Roll back user creation if email sending fails
        $user->delete();
       return response()->json(['massege'=>'Registration failed. Please try again later.',                'error' => $e->getMessage()]
       , 403);
    }
}
public function verifyEmail(Request $request)
{
    $request->validate([
        'national_id' => 'required_if:type,citizen,government-employee',
        'representative_national_id' => 'required_if:type,government-institute',
        'type' => 'required|in:citizen,government-institute,government-employee',
        'code' => 'required'
    ]);

    $user = User::where('national_id', $request->national_id)
        ->where('type', $request->type)
        ->first();
        if (!$user) {
            return response()->json('User not found.', 404);
        }

    if (         $user->email_verified_code !== $request->code ||
    !$user->email_verified_code_expiry ||
    now()->greaterThan($user->email_verified_code_expiry)) {
        return response()->json('Invalid or expired verification code.', 400);
    }

    $user->update([
        'status' => 'active',
        'verified_at' => now(),
        'email_verified_code' => null,
        'email_verified_code_expiry' => null,

    ]);

    return response()->json(['massege'=>'Email verified successfully.',
        'user' => $user,
    ],200);
}


public function login(Request $request)
{
    $validated = $request->validate([
        'national_id' => 'required_if:type,citizen,government-employee',
        'type' => 'required|in:citizen,government-institute,government-employee',
        'password' => 'required|string',
    ]);

    // Retrieve the user by national_id or representative_national_id and type

        $query = User::where('type', $request->type);

        if ($request->type === 'government-institute') {
            $query->where(function ($q) use ($request) {
                $q->where('national_id', $request->national_id)
                ->orWhere('representative_national_id', $request->national_id);
            });
        } else {
            $query->where('national_id', $request->national_id);
        }

        $user = $query->first();

    // Check if user exists and password is correct
    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        return response()->json([
            'message' => 'Login information is invalid.'
        ], 401);
    }

    // Check if the account is activated (email verified)
    if ($user->status !== 'active') {
        return response()->json([
            'message' => 'Your account is not activated. Please verify your email.'
        ], 403);
    }

    // Generate personal access token using Laravel Sanctum
    $token = $user->createToken('L_Token')->plainTextToken;

    // Return success response with token and user data
    return response()->json([
        'message' => 'Authenticated successfully.',
        'data' => [
            'national_id' => $user->national_id,
            'representative_national_id' => $user->representative_national_id,

            'type' => $user->type,
            'token' => $token,
            'token_type' => 'Bearer',
        ]
    ], 200);
}



public function forgetPassword(Request $request)
{
    $validated = $request->validate([
        'national_id' => 'required_if:type,citizen,government-employee',
        'type' => 'required|in:citizen,government-institute,government-employee',
        'email' => 'required|email',
    ]);
    // Dynamically choose the correct national ID field
    $user = null;

    switch ($validated['type']) {
        case 'citizen':
        case 'government-employee':
            $user = User::where('national_id', $validated['national_id'])
                        ->where('type', $validated['type'])
                        ->where('email', $validated['email'])
                        ->first();
            break;

        case 'government-institute':
            $user = User::where('representative_national_id', $validated['national_id'])
                        ->where('type', $validated['type'])
                        ->where('email', $validated['email'])
                        ->first();
            break;
    }

    if (!$user) {
        return $this->error('User not found.', 404);
    }

    // Generate a reset token
    $token = Str::random(64);

    // Check if a token already exists for this email and type
    $resetToken = PasswordResetToken::where('email', $user->email)
                                    ->where('type', $user->type)
                                    ->first();

    if ($resetToken) {
        $updated = $resetToken->update([
            'token' => $token,
            'created_at' => now(),
        ]);

        if (!$updated) {
            return response()->json('Failed to update reset token.', 500);
        }
    } else {
        $created = PasswordResetToken::create([
            'email' => $user->email,
            'type' => $user->type,
            'token' => $token,
            'created_at' => now(),
        ]);

        if (!$created) {
            return response()->json('Failed to create reset token.', 500);
        }
    }

    // Send reset email
    try {
        Mail::to($user->email)->send(new ResetPasswordMail($token, $user->email));
        return response()->json(['massege'=>'Password reset token sent successfully.',
            'email' => $user->email],
            200
        );
    } catch (Exception $e) {
        return response()->json('Failed to send email. Please try again later.', 500);
    }
}


public function resetPassword(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'type' => 'required|in:citizen,government-institute,government-employee',
        'token' => 'required|string|exists:password_reset_tokens,token',
        'new_password' => 'required|min:8|confirmed',
    ]);

    // 1. Get the token record
    $tokenRecord = PasswordResetToken::where('email', $validated['email'])
        ->where('type', $validated['type'])
        ->where('token', $validated['token'])
        ->first();

    if (!$tokenRecord) {
        return response()->json('Invalid or expired token.', 400);
    }

    // 2. Get the user
    $user = User::where('email', $validated['email'])
        ->where('type', $validated['type'])
        ->first();

    if (!$user) {
        return response()->json('User not found.', 404);
    }

    // 3. Update the password
    $user->password = Hash::make($validated['new_password']);
    $user->save();

    // 4. Remove the token
    $tokenRecord->delete();

    return response()->json(['massege'=>'Password reset successfully.',
        'email' => $user->email,
    ], 200);
}
public function changePassword(Request $request)
{
    $validated = $request->validate([
        'password' => 'required|string|min:8',
        'new_password' => 'required|string|min:8',
    ]);
    $user = Auth::user();
    if (!Hash::check($validated['password'], $user->password)) {
        return response()->json('Current password is incorrect.', 400);
    }
    try {
        $user->password = Hash::make($validated['new_password']);
        $user->save();
        return response()->json(['massege'=>'Password changed successfully.',
            'national_id' => $user->national_id,
            'representative_national_id' => $user->representative_national_id,
            'type' => $user->type,
        ], 200);
    } catch (Exception $e) {
        return response()->json('Failed to update the password. Please try again later.', 500);
    }
}

}




