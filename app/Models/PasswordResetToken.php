<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'type',
        'token',
        'created_at',
    ];
}
