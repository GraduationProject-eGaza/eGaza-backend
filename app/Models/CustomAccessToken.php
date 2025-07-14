<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class CustomAccessToken extends SanctumPersonalAccessToken
{

        protected $table = 'personal_access_tokens';

    /**
     * Fix morph relation for tokenable
     */
    public function tokenable()
    {
        return $this->morphTo(); // fix to avoid morph map errors
    }
}
