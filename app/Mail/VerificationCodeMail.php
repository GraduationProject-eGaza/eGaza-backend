<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class VerificationCodeMail extends Mailable
{

    public function __construct(public string $code) {}

    public function build()
    {
        return $this->view('email.verify')
            ->subject('Your Verification Code')
            ->with(['code' => $this->code,
        ]);
    }
}
