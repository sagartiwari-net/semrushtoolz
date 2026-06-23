<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolSession extends Model
{
    protected $fillable = [
        'user_id', 'tool', 'cloud_account_id', 'proxy_session_id',
        'status', 'ip_address', 'access_url', 'started_at', 'ended_at', 'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cloudAccount(): BelongsTo
    {
        return $this->belongsTo(CloudAccount::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ended_at === null;
    }

    public function minutesRemaining(): int
    {
        $maxMinutes = config('tools.session_max_minutes', 120);
        $elapsed = $this->started_at->diffInMinutes(now());

        return max(0, $maxMinutes - $elapsed);
    }
}
