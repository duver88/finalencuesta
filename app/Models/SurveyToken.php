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
        'last_attempt_at',
        'reserved_at',
        'reserved_by_session',
        'reserved_by_device',
        'reservation_expires_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'reserved_at' => 'datetime',
        'reservation_expires_at' => 'datetime',
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
            'last_attempt_at' => now(),
            // Limpiar campos de reserva cuando se usa el token
            'reserved_at' => null,
            'reserved_by_session' => null,
            'reserved_by_device' => null,
            'reservation_expires_at' => null
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('vote_attempts');
        $this->update(['last_attempt_at' => now()]);
    }

    public function isValid(): bool
    {
        // Un token es válido si está pending O reserved (aún no usado)
        return $this->status === 'pending' || $this->status === 'reserved';
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Reservar el token para una sesión específica por 5 minutos
     */
    public function reserve(string $sessionId, ?string $deviceFingerprint = null): void
    {
        $this->update([
            'status' => 'reserved',
            'reserved_at' => now(),
            'reserved_by_session' => $sessionId,
            'reserved_by_device' => $deviceFingerprint,
            'reservation_expires_at' => now()->addMinutes(5)
        ]);
    }

    /**
     * Verificar si el token está reservado
     */
    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Liberar la reserva del token
     */
    public function releaseReservation(): void
    {
        $this->update([
            'status' => 'pending',
            'reserved_at' => null,
            'reserved_by_session' => null,
            'reserved_by_device' => null,
            'reservation_expires_at' => null
        ]);
    }

    /**
     * Verificar si la reserva ha expirado
     */
    public function hasExpiredReservation(): bool
    {
        if ($this->status !== 'reserved') {
            return false;
        }

        return $this->reservation_expires_at && $this->reservation_expires_at->isPast();
    }

    /**
     * Liberar todas las reservas expiradas (método estático)
     */
    public static function releaseExpiredReservations(): int
    {
        return self::where('status', 'reserved')
            ->where('reservation_expires_at', '<=', now())
            ->update([
                'status' => 'pending',
                'reserved_at' => null,
                'reserved_by_session' => null,
                'reserved_by_device' => null,
                'reservation_expires_at' => null
            ]);
    }

    /**
     * Buscar token reservado por esta sesión
     */
    public static function findReservedBySession(int $surveyId, string $sessionId): ?self
    {
        return self::where('survey_id', $surveyId)
            ->where('reserved_by_session', $sessionId)
            ->where('status', 'reserved')
            ->where('reservation_expires_at', '>', now())
            ->first();
    }
}
