<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;
use HasFactory;

    protected $fillable = [
        'citizen_id',
        'institute_id',
        'title',
        'description',
        'complaint_date',
        'status',
        'assigned_to',
        'complaint_number',
    ];

    public function citizen() {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function institute() {
        return $this->belongsTo(User::class, 'institute_id');
    }

    public function assignedEmployee() {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
