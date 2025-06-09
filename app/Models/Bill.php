<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;
    protected $fillable = [
        'bill_type_id', 'assigned_by', 'citizen_id', 'amount', 'due_date', 'status', 'paid_at','bill_number'
    ];

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function billType()
    {
        return $this->belongsTo(BillType::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
