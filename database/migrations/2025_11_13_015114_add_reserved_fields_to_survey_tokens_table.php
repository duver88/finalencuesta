<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survey_tokens', function (Blueprint $table) {
            // Agregar campos para reserva de tokens
            $table->timestamp('reserved_at')->nullable()->after('status');
            $table->string('reserved_by_session')->nullable()->after('reserved_at');
            $table->timestamp('reservation_expires_at')->nullable()->after('reserved_by_session');

            // Agregar índice para búsquedas rápidas de reservas
            $table->index(['reserved_by_session', 'reservation_expires_at']);
        });

        // Actualizar el ENUM de status para incluir 'reserved'
        DB::statement("ALTER TABLE survey_tokens MODIFY COLUMN status ENUM('pending', 'reserved', 'used', 'expired') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_tokens', function (Blueprint $table) {
            $table->dropIndex(['reserved_by_session', 'reservation_expires_at']);
            $table->dropColumn(['reserved_at', 'reserved_by_session', 'reservation_expires_at']);
        });

        // Revertir el ENUM de status
        DB::statement("ALTER TABLE survey_tokens MODIFY COLUMN status ENUM('pending', 'used', 'expired') NOT NULL DEFAULT 'pending'");
    }
};
