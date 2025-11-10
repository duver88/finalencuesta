<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyToken extends Model
{
    protected $fillable = [
        'survey_id',
        'token',
        'source',
        'campaign_id',
        'status',
        'used_at',
        'used_by_fingerprint',
        'user_agent',
        'vote_attempts',
        'last_attempt_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'vote_attempts' => 'integer'
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function markAsUsed(string $fingerprint, string $userAgent): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_by_fingerprint' => $fingerprint,
            'user_agent' => $userAgent,
            'vote_attempts' => $this->vote_attempts + 1,
            'last_attempt_at' => now()
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('vote_attempts');
        $this->update(['last_attempt_at' => now()]);
    }

    public function isValid(): bool
    {
        return $this->status === 'pending';
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}
