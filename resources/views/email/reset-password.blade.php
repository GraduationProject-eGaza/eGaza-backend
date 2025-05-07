<h1>Password Reset Request</h1>
<p>Click the link below to reset your password:</p>
<a href="{{ url('/reset-password?token=' . $token . '&email=' . $email) }}">
    Reset Password
</a>
