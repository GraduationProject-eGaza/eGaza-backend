<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;
        protected $fillable = ['institute_id', 'name', 'description'];

    public function institute()
    {
        return $this->belongsTo(User::class, 'institute_id');
    }

    public function assignedEmployees()
    {
        return $this->belongsToMany(User::class, 'service_type_user', 'service_type_id', 'user_id')
                    ->where('type', 'government-employee');
    }

    /**
     * One-to-Many: Service requests submitted for this service type
     * Assuming you have a `ServiceRequest` model with `service_type_id` foreign key
     */
public function serviceRequests()
{
    return $this->hasMany(ServiceRequest::class);
}
}


