<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'full_name',
        'username',
        'national_id',
        'email',
        'phone',
        'address',
        'password',
        'status',
        'email_verified_code',
        'email_verified_code_expiry',
        'email_verified_at',
        'bio',
        'profile_picture',

        // Government Institute fields
        'institution_name',
        'institution_type',
        'institution_email',
        'official_phone',
        'government_id',
        'institution_address',
        'representative_national_id',
        'representative_mobile',

        // Government Employee field
        'employee_id',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verified_code_expiry' => 'datetime',
    ];
}
