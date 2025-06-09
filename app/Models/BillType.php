<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillType extends Model
{
    use HasFactory;
        protected $fillable = ['institute_id', 'name', 'description', 'default_amount'];

    public function assignedEmployees()
    {
        return $this->belongsToMany(User::class, 'bill_type_user', 'bill_type_id', 'user_id');
    }

    public function institute()
    {
        return $this->belongsTo(User::class, 'institute_id');
    }
}
