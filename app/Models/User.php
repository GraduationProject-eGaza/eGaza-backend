<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Region;
use App\Models\FriendRequest;
use App\Models\Announcement;
use App\Models\ServiceType;
use App\Models\SocialLink;


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
        'gender',
        'date_of_birth',
        'governorate',
        'city',
        'street',
        'nearest_landmark',
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
        'institute_id',
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
    // app/Models/User.php

    public function sentFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id');
    }
    // For Citizen
    public function followingInstitutes()
    {
        return $this->belongsToMany(User::class, 'follows', 'citizen_id', 'government_institute_id');
    }



      // Citizens following institutes
    public function followedInstitutes()
    {
        return $this->belongsToMany(User::class, 'citizen_follows', 'citizen_id', 'institute_id');
    }

    // Institutes followed by citizens
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'government_institute_id', 'citizen_id');
    }

    // Announcements created by employees
    public function announcementsCreated()
    {
        return $this->hasMany(Announcement::class, 'employee_id');
    }

    // Announcements associated with an institute
    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'institute_id');
    }
// Employee -> belongs to one institute
public function institute()
{
    return $this->belongsTo(User::class, 'institute_id')->where('type', 'government-institute');
}
// Institute -> has many employees
public function employees()
{
    return $this->hasMany(User::class, 'institute_id')->where('type', 'government-employee');
}

// For government-employee: services they are assigned to
public function assignedServiceTypes()
{
    return $this->belongsToMany(ServiceType::class, 'service_type_user', 'user_id', 'service_type_id');
}
// For government-institute: services they created
public function serviceTypes()
{
    return $this->hasMany(ServiceType::class, 'institute_id');
}

public function socialLinks()
{
        return $this->hasMany(SocialLink::class, 'user_id');
}

}
