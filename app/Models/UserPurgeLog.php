<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPurgeLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'name',
        'reason',
        'triggered_by',
        'purged_at',
    ];

    protected function casts(): array
    {
        return [
            'purged_at' => 'datetime',
        ];
    }
}
