<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'institute_id', 'title', 'description', 'announcement_date', 'media_path', 'status'];



    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function institute()
    {
        return $this->belongsTo(User::class, 'institute_id');
    }

    public function comments()
    {
        return $this->hasMany(AnnouncementComment::class);
    }

    public function likes()
    {
        return $this->hasMany(AnnouncementLike::class);
    }

    public function views()
    {
        return $this->hasMany(AnnouncementView::class);
    }
}
