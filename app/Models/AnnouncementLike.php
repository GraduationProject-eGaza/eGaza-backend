<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementLike extends Model
{
    use HasFactory;
    protected $fillable = ['announcement_id', 'citizen_id'];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }
}
