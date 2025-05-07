<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        $resetUrl = url('/reset-password?token=' . $this->token . '&email=' . $this->email);
        return $this->subject('Password Reset Request')
            ->view('email.reset-password')
            ->with('resetUrl', $resetUrl);
    }
}
