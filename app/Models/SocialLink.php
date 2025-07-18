<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'platform', 'link'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
