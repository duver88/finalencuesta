<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->string('device_fingerprint')->nullable()->after('fingerprint');
            $table->index(['survey_id', 'device_fingerprint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['survey_id', 'device_fingerprint']);
            $table->dropColumn('device_fingerprint');
        });
    }
};
