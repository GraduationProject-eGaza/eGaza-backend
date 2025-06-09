<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = ['citizen_id', 'government_institute_id'];
    public function institute()
{
    return $this->belongsTo(User::class, 'government_institute_id');
}
}
