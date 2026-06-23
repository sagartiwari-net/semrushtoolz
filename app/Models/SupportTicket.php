<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'subject',
        'priority',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public static function generateNumber(): string
    {
        $lastId = (int) static::query()->max('id');

        return 'TKT-'.str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REPLIED => 'Replied',
            self::STATUS_CLOSED => 'Closed',
            default => 'Open',
        };
    }

    public function priorityLabel(): string
    {
        return ucfirst($this->priority);
    }
}
