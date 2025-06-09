<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'institute_id',
        'service_type_id',
        'description',
        'request_date',
        'status',
        'service_number'
    ];
    protected $with = ['institute', 'serviceType']; // auto-load in case of serialization elsewhere
protected $appends = ['service_number'];


    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function institute()
    {
        return $this->belongsTo(User::class, 'institute_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

        public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
