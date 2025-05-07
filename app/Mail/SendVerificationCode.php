<?php

namespace App\Mail;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendVerificationCode
{
    public function sendEmail(User $user)
    {

        // Generate a random verification code and set the expiry time
        $verificationCode = rand(10000, 99999);
        $expiryTime = env('CODE_VERIFICATION_DELAY', 10);

        // Update the user record with the verification code and expiry time
        $user->update([
            'email_verified_code' => $verificationCode,
            'email_verified_code_expiry' => now()->addMinutes($expiryTime),
        ]);

        // sending the verification email
        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
    }
}
