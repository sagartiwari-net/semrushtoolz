<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailNotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'preset_key',
        'reference_type',
        'reference_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function alreadySent(
        int $userId,
        string $presetKey,
        string $referenceType,
        int $referenceId,
    ): bool {
        return static::query()->where([
            'user_id' => $userId,
            'preset_key' => $presetKey,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ])->exists();
    }

    public static function record(
        int $userId,
        string $presetKey,
        string $referenceType,
        int $referenceId,
    ): void {
        static::query()->firstOrCreate([
            'user_id' => $userId,
            'preset_key' => $presetKey,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ], [
            'sent_at' => now(),
        ]);
    }
}
